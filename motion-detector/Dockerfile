FROM python:3.11-slim

# ffmpeg utk rekam klip RTSP; libgl1/libglib utk OpenCV (cv2)
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    libgl1 \
    libglib2.0-0 \
    tzdata \
    wget \
    ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Model YOLOv4-tiny (80 kelas COCO) - di-download saat build, self-contained
RUN mkdir -p /app/models && cd /app/models \
    && wget -q https://raw.githubusercontent.com/AlexeyAB/darknet/master/cfg/yolov4-tiny.cfg \
    && wget -q https://raw.githubusercontent.com/AlexeyAB/darknet/master/data/coco.names \
    && wget -q https://github.com/AlexeyAB/darknet/releases/download/yolov4/yolov4-tiny.weights

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY detect.py .

CMD ["python", "-u", "detect.py"]
