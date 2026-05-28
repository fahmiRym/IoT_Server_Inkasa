import os
import time
import subprocess
from datetime import datetime

import cv2
import numpy as np
import requests

# RTSP over TCP harus diset SEBELUM VideoCapture membuka stream.
os.environ.setdefault("OPENCV_FFMPEG_CAPTURE_OPTIONS", "rtsp_transport;tcp")

# Pakai timezone lokal (TZ env, mis. Asia/Jakarta) agar jam caption = jam CCTV.
try:
    time.tzset()
except Exception:
    pass

RTSP_URL  = os.environ.get("RTSP_URL", "rtsp://127.0.0.1:8554/cctv1")
BOT_TOKEN = os.environ["TELEGRAM_BOT_TOKEN"]
CHAT_ID   = os.environ["TELEGRAM_CHAT_ID"]
MIN_AREA  = int(os.environ.get("MIN_AREA", "1500"))
CONSEC    = int(os.environ.get("CONSEC_FRAMES", "3"))
COOLDOWN  = int(os.environ.get("COOLDOWN_SECONDS", "60"))
REC_SECS  = int(os.environ.get("RECORD_SECONDS", "8"))
CAP_DIR   = os.environ.get("CAPTURE_DIR", "/captures")
LABEL     = os.environ.get("LOCATION_LABEL", "Ruang Server")
YOLO_CONF = float(os.environ.get("YOLO_CONF", "0.4"))
MODEL_DIR = os.environ.get("MODEL_DIR", "/app/models")
ALERT_ONLY_PERSON = os.environ.get("ALERT_ONLY_PERSON", "false").lower() in ("1", "true", "yes")

os.makedirs(CAP_DIR, exist_ok=True)
TG = f"https://api.telegram.org/bot{BOT_TOKEN}"

# --- YOLOv4-tiny (OpenCV DNN, CPU) ---
with open(os.path.join(MODEL_DIR, "coco.names")) as f:
    CLASSES = [c.strip() for c in f if c.strip()]
_net = cv2.dnn.readNetFromDarknet(
    os.path.join(MODEL_DIR, "yolov4-tiny.cfg"),
    os.path.join(MODEL_DIR, "yolov4-tiny.weights"),
)
_net.setPreferableBackend(cv2.dnn.DNN_BACKEND_OPENCV)
_net.setPreferableTarget(cv2.dnn.DNN_TARGET_CPU)
MODEL = cv2.dnn_DetectionModel(_net)
MODEL.setInputParams(size=(416, 416), scale=1 / 255.0, swapRB=True)

# label Indonesia utk kelas yg umum di ruang server
ID_NAME = {"person": "orang", "backpack": "tas", "handbag": "tas", "suitcase": "koper",
           "laptop": "laptop", "cell phone": "hp", "chair": "kursi", "tvmonitor": "monitor",
           "tv": "monitor", "keyboard": "keyboard", "mouse": "mouse", "bottle": "botol"}


def tg_text(text):
    try:
        requests.post(f"{TG}/sendMessage",
                      data={"chat_id": CHAT_ID, "text": text, "parse_mode": "Markdown"}, timeout=20)
    except Exception as e:
        print("tg_text error:", e, flush=True)


def tg_photo(path, caption):
    try:
        with open(path, "rb") as f:
            requests.post(f"{TG}/sendPhoto",
                          data={"chat_id": CHAT_ID, "caption": caption, "parse_mode": "Markdown"},
                          files={"photo": f}, timeout=60)
    except Exception as e:
        print("tg_photo error:", e, flush=True)


def tg_video(path, caption):
    try:
        with open(path, "rb") as f:
            requests.post(f"{TG}/sendVideo",
                          data={"chat_id": CHAT_ID, "caption": caption,
                                "parse_mode": "Markdown", "supports_streaming": True},
                          files={"video": f}, timeout=180)
    except Exception as e:
        print("tg_video error:", e, flush=True)


def record_clip(path):
    cmd = ["ffmpeg", "-y", "-rtsp_transport", "tcp", "-i", RTSP_URL,
           "-t", str(REC_SECS), "-c", "copy", "-movflags", "+faststart", path]
    subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=REC_SECS + 30)


def detect_objects(frame):
    classIds, confs, boxes = MODEL.detect(frame, confThreshold=YOLO_CONF, nmsThreshold=0.45)
    if len(boxes) == 0:
        return []
    ids = np.array(classIds).reshape(-1)
    cfs = np.array(confs).reshape(-1)
    out = []
    for cid, cf, box in zip(ids, cfs, boxes):
        name = CLASSES[int(cid)] if int(cid) < len(CLASSES) else str(int(cid))
        out.append((name, float(cf), tuple(int(v) for v in box)))
    return out


def capture_burst(cap, backsub, n=15, keep=4):
    """Burst ~1.2 dtk, ambil beberapa frame dgn gerakan terbesar (kandidat orang)."""
    scored = []
    for _ in range(n):
        ok, fr = cap.read()
        if not ok:
            continue
        small = cv2.resize(fr, (640, 360))
        gray = cv2.GaussianBlur(cv2.cvtColor(small, cv2.COLOR_BGR2GRAY), (21, 21), 0)
        mask = backsub.apply(gray)
        _, th = cv2.threshold(mask, 200, 255, cv2.THRESH_BINARY)
        th = cv2.dilate(th, None, iterations=2)
        cnts, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        total = sum(a for a in (cv2.contourArea(c) for c in cnts) if a > MIN_AREA)
        if total > 0:
            scored.append((total, fr.copy()))
    scored.sort(key=lambda x: x[0], reverse=True)
    return [fr for _, fr in scored[:keep]]


def pick_frame(frames, fallback):
    """Pilih frame dgn confidence 'orang' tertinggi; kalau tak ada orang, pakai gerakan terbesar."""
    best, best_p, best_dets = None, -1.0, []
    for fr in frames:
        dets = detect_objects(fr)
        pconf = max([d[1] for d in dets if d[0] == "person"], default=0.0)
        if pconf > best_p:
            best, best_p, best_dets = fr, pconf, dets
    if best is not None and best_p > 0:
        return best, best_dets
    if frames:
        return frames[0], detect_objects(frames[0])
    return fallback, detect_objects(fallback)


def open_stream():
    cap = cv2.VideoCapture(RTSP_URL, cv2.CAP_FFMPEG)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    return cap


def main():
    print(f"Motion detector start | YOLOv4-tiny | source={RTSP_URL} | now={datetime.now()}", flush=True)
    tg_text(f"✅ *Motion detector aktif* (YOLO) — memantau {LABEL}")

    cap = open_stream()
    backsub = cv2.createBackgroundSubtractorMOG2(history=500, varThreshold=40, detectShadows=False)
    motion_run, last_alert, warmup, fail = 0, 0.0, 30, 0

    while True:
        ok, frame = cap.read()
        if not ok:
            fail += 1
            print(f"read failed ({fail}), reconnecting...", flush=True)
            cap.release()
            time.sleep(2)
            cap = open_stream()
            if fail % 30 == 0:
                tg_text(f"⚠️ Stream {LABEL} terputus, mencoba sambung ulang...")
            continue
        fail = 0

        small = cv2.resize(frame, (640, 360))
        gray = cv2.GaussianBlur(cv2.cvtColor(small, cv2.COLOR_BGR2GRAY), (21, 21), 0)
        mask = backsub.apply(gray)
        if warmup > 0:
            warmup -= 1
            continue

        _, th = cv2.threshold(mask, 200, 255, cv2.THRESH_BINARY)
        th = cv2.dilate(th, None, iterations=2)
        cnts, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        moving = any(cv2.contourArea(c) > MIN_AREA for c in cnts)
        motion_run = motion_run + 1 if moving else max(0, motion_run - 1)

        if motion_run >= CONSEC and (time.time() - last_alert) > COOLDOWN:
            last_alert = time.time()
            motion_run = 0
            nice = datetime.now().strftime("%d %b %Y %H:%M:%S")
            ts = datetime.now().strftime("%Y%m%d_%H%M%S")

            frames = capture_burst(cap, backsub)
            best, dets = pick_frame(frames, frame)
            people = [d for d in dets if d[0] == "person"]

            if ALERT_ONLY_PERSON and not people:
                print(f"skip (no person) @ {nice}", flush=True)
                continue

            for name, cf, (x, y, w, h) in dets:
                color = (0, 255, 0) if name == "person" else (255, 200, 0)
                tag = ID_NAME.get(name, name)
                cv2.rectangle(best, (x, y), (x + w, y + h), color, 2)
                cv2.putText(best, f"{tag} {cf:.2f}", (x, max(15, y - 6)),
                            cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
            cv2.putText(best, nice, (10, 25), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)

            objs = ", ".join(sorted({ID_NAME.get(d[0], d[0]) for d in dets})) or "-"
            if people:
                title = f"\U0001f6a8 *ORANG MASUK* di {LABEL} ({len(people)} orang)"
                print(f"PERSON @ {nice} ({len(people)})", flush=True)
            else:
                title = f"\U0001f6a8 *Pergerakan terdeteksi* di {LABEL}"
                print(f"MOTION @ {nice} objek=[{objs}]", flush=True)

            snap = os.path.join(CAP_DIR, f"motion_{ts}.jpg")
            cv2.imwrite(snap, best)
            tg_photo(snap, f"{title}\n\U0001f552 {nice}\n\U0001f50d Objek: {objs}")

            clip = os.path.join(CAP_DIR, f"motion_{ts}.mp4")
            try:
                record_clip(clip)
                if os.path.exists(clip) and os.path.getsize(clip) > 0:
                    tg_video(clip, f"\U0001f3a5 Rekaman {REC_SECS}s — {LABEL} {nice}")
            except Exception as e:
                print("record error:", e, flush=True)

            for _ in range(10):
                cap.read()


if __name__ == "__main__":
    main()
