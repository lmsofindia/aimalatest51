"""
quizaccess_edproctoring — Face Recognition FastAPI Microservice
Phase 2 — run on same server as Moodle.

Install:
    pip install deepface fastapi uvicorn python-multipart pillow

Run (production):
    uvicorn main:app --host 127.0.0.1 --port 8765

The service ONLY listens on localhost — never expose to the internet.
Moodle's PHP calls this via cURL from the server itself.
"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional
import os
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="EDP Face Recognition Service",
    description="On-premises face recognition for quizaccess_edproctoring",
    version="1.0.0",
)

# Lazy-load DeepFace to avoid slow startup.
_deepface = None

def get_deepface():
    global _deepface
    if _deepface is None:
        from deepface import DeepFace
        _deepface = DeepFace
    return _deepface


# --------------------------------------------------------------------------
# Request / Response models
# --------------------------------------------------------------------------

class VerifyRequest(BaseModel):
    image1_path: str              # Absolute path to first image (snapshot)
    image2_path: str              # Absolute path to second image (base image)
    model: str = "ArcFace"        # ArcFace | VGG-Face | Facenet | DeepFace
    distance_metric: str = "cosine"


class VerifyResponse(BaseModel):
    verified: bool
    distance: float
    threshold: float
    model: str
    detector_backend: str
    similarity_metric: str


class DetectRequest(BaseModel):
    image_path: str


class FaceBBox(BaseModel):
    x: int
    y: int
    w: int
    h: int
    confidence: float


class DetectResponse(BaseModel):
    face_count: int
    faces: list[FaceBBox]


# --------------------------------------------------------------------------
# Health check
# --------------------------------------------------------------------------

@app.get("/health")
def health():
    """Moodle pings this to verify service connectivity."""
    return {"status": "ok", "model": "ArcFace"}


# --------------------------------------------------------------------------
# Face verification
# --------------------------------------------------------------------------

@app.post("/verify", response_model=VerifyResponse)
def verify_faces(req: VerifyRequest):
    """
    Compare two images for face match.
    Returns verified=true if the same person, distance < threshold.
    """
    for path in [req.image1_path, req.image2_path]:
        if not os.path.isfile(path):
            raise HTTPException(status_code=400, detail=f"Image not found: {path}")

    try:
        df = get_deepface()
        result = df.verify(
            img1_path=req.image1_path,
            img2_path=req.image2_path,
            model_name=req.model,
            distance_metric=req.distance_metric,
            enforce_detection=False,  # Don't crash if face not detected — return unverified.
        )
        return VerifyResponse(
            verified=result["verified"],
            distance=round(result["distance"], 4),
            threshold=round(result["threshold"], 4),
            model=result["model"],
            detector_backend=result.get("detector_backend", "opencv"),
            similarity_metric=result["similarity_metric"],
        )
    except Exception as e:
        logger.error(f"Verification error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# --------------------------------------------------------------------------
# Face detection (count + bounding boxes)
# --------------------------------------------------------------------------

@app.post("/detect", response_model=DetectResponse)
def detect_faces(req: DetectRequest):
    """
    Detect all faces in an image. Returns count and bounding boxes.
    Used as server-side fallback if browser face-api.js is unavailable.
    """
    if not os.path.isfile(req.image_path):
        raise HTTPException(status_code=400, detail=f"Image not found: {req.image_path}")

    try:
        df = get_deepface()
        faces = df.extract_faces(
            img_path=req.image_path,
            detector_backend="opencv",
            enforce_detection=False,
        )
        bbox_list = []
        for face in faces:
            area = face.get("facial_area", {})
            bbox_list.append(FaceBBox(
                x=area.get("x", 0),
                y=area.get("y", 0),
                w=area.get("w", 0),
                h=area.get("h", 0),
                confidence=round(face.get("confidence", 0.0), 4),
            ))

        return DetectResponse(face_count=len(bbox_list), faces=bbox_list)

    except Exception as e:
        logger.error(f"Detection error: {e}")
        raise HTTPException(status_code=500, detail=str(e))
