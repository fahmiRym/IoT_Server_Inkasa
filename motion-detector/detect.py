import os
import time
import subprocess
from datetime import datetime

import cv2
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
MIN_AREA  = int(os.environ.get("MIN_AREA", "1500"))       # luas blob (pixel) minimum dianggap gerakan
CONSEC    = int(os.environ.get("CONSEC_FRAMES", "3"))     # butuh N frame berturut agar tdk false-alarm
COOLDOWN  = int(os.environ.get("COOLDOWN_SECONDS", "60")) # jeda antar notifikasi
REC_SECS  = int(os.environ.get("RECORD_SECONDS", "8"))    # durasi klip rekaman
CAP_DIR   = os.environ.get("CAPTURE_DIR", "/captures")
LABEL     = os.environ.get("LOCATION_LABEL", "Ruang Server")

os.makedirs(CAP_DIR, exist_ok=True)
TG = f"https://api.telegram.org/bot{BOT_TOKEN}"

# HOG people detector bawaan OpenCV (tanpa download model).
hog = cv2.HOGDescriptor()
hog.setSVMDetector(cv2.HOGDescriptor_getDefaultPeopleDetector())


def tg_text(text):
    try:
        requests.post(f"{TG}/sendMessage",
                      data={"chat_id": CHAT_ID, "text": text, "parse_mode": "Markdown"},
                      timeout=20)
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
    # -c copy: simpan h264 asli kamera (tanpa re-encode) -> ringan & langsung diputar Telegram.
    cmd = ["ffmpeg", "-y", "-rtsp_transport", "tcp", "-i", RTSP_URL,
           "-t", str(REC_SECS), "-c", "copy", "-movflags", "+faststart", path]
    subprocess.run(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=REC_SECS + 30)


def detect_people(frame):
    # HOG di lebar 640 (cukup akurat & cepat); kembalikan kotak skala frame asli.
    h0, w0 = frame.shape[:2]
    scale = 640.0 / w0
    small = cv2.resize(frame, (640, int(h0 * scale)))
    rects, _ = hog.detectMultiScale(small, winStride=(8, 8), padding=(8, 8), scale=1.05)
    return [(int(x / scale), int(y / scale), int(w / scale), int(h / scale)) for (x, y, w, h) in rects]


def capture_best(cap, backsub):
    """Ambil burst ~1.2 dtk, pilih frame dgn gerakan terbesar (paling mungkin ada orang penuh)."""
    best_frame, best_area, best_boxes = None, -1.0, []
    for _ in range(15):
        ok, fr = cap.read()
        if not ok:
            continue
        small = cv2.resize(fr, (640, 360))
        gray = cv2.GaussianBlur(cv2.cvtColor(small, cv2.COLOR_BGR2GRAY), (21, 21), 0)
        mask = backsub.apply(gray)
        _, th = cv2.threshold(mask, 200, 255, cv2.THRESH_BINARY)
        th = cv2.dilate(th, None, iterations=2)
        cnts, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        sx, sy = fr.shape[1] / 640.0, fr.shape[0] / 360.0
        total, boxes = 0.0, []
        for c in cnts:
            a = cv2.contourArea(c)
            if a > MIN_AREA:
                total += a
                x, y, w, h = cv2.boundingRect(c)
                boxes.append((int(x * sx), int(y * sy), int(w * sx), int(h * sy)))
        if total > best_area:
            best_area, best_frame, best_boxes = total, fr.copy(), boxes
    return best_frame, best_boxes


def open_stream():
    cap = cv2.VideoCapture(RTSP_URL, cv2.CAP_FFMPEG)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
    return cap


def main():
    print(f"Motion detector start | source={RTSP_URL} | label={LABEL} | now={datetime.now()}", flush=True)
    tg_text(f"✅ *Motion detector aktif* — memantau {LABEL}")

    cap = open_stream()
    backsub = cv2.createBackgroundSubtractorMOG2(history=500, varThreshold=40, detectShadows=False)
    motion_run = 0
    last_alert = 0.0
    warmup = 30
    fail = 0

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

            # pilih frame terbaik dari burst
            best, boxes = capture_best(cap, backsub)
            if best is None:
                best, boxes = frame, []

            people = detect_people(best)
            for (x, y, w, h) in people:                     # kotak hijau = orang
                cv2.rectangle(best, (x, y), (x + w, y + h), (0, 255, 0), 2)
            for (x, y, w, h) in boxes:                       # kotak merah = area gerakan
                cv2.rectangle(best, (x, y), (x + w, y + h), (0, 0, 255), 2)
            cv2.putText(best, nice, (10, 25), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 255), 2)

            if people:
                title = f"\U0001f6a8 *ORANG MASUK* di {LABEL}"
                print(f"PERSON @ {nice} ({len(people)} orang)", flush=True)
            else:
                title = f"\U0001f6a8 *Pergerakan terdeteksi* di {LABEL}"
                print(f"MOTION @ {nice}", flush=True)

            snap = os.path.join(CAP_DIR, f"motion_{ts}.jpg")
            cv2.imwrite(snap, best)
            tg_photo(snap, f"{title}\n\U0001f552 {nice}")

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
