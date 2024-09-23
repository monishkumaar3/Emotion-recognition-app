import os
from flask import Flask, request, jsonify, render_template, redirect, url_for
from werkzeug.utils import secure_filename
import cv2
import numpy as np
import torch
from transformers import AutoModelForSequenceClassification, AutoTokenizer

app = Flask(__name__)

# Directory to save uploaded images
# UPLOAD_FOLDER = r"C:\Users\monis\OneDrive\Desktop\text_rec_model\samples"
UPLOAD_FOLDER = r"D:\xampp\htdocs\kcg\posts"
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER 

# Allowed extensions for image uploads
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg'}

# YOLOv3 paths
weights_path = r"C:\Users\monis\OneDrive\Desktop\text_rec_model\yolov3.weights"
config_path = r"C:\Users\monis\OneDrive\Desktop\text_rec_model\yolov3.cfg"
coco_names_path = r"C:\Users\monis\OneDrive\Desktop\text_rec_model\coco.names"

# Load YOLO model
net = cv2.dnn.readNet(weights_path, config_path)
layer_names = net.getLayerNames()
output_layers = [layer_names[i - 1] for i in net.getUnconnectedOutLayers()]

# Load COCO class labels
with open(coco_names_path, "r") as f:
    classes = [line.strip() for line in f.readlines()]

# Function to check allowed file types
def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

# Function to detect objects using YOLO and return the image path with bounding boxes and detected labels
def detect_objects(image_path):
    # Load image
    img = cv2.imread(image_path)
    height, width, channels = img.shape

    # Prepare the image for YOLO
    blob = cv2.dnn.blobFromImage(img, 0.00392, (416, 416), (0, 0, 0), True, crop=False)
    net.setInput(blob)
    outs = net.forward(output_layers)

    # Info to show on the screen
    class_ids = []
    confidences = []
    boxes = []
    detected_labels = []

    # Analyze the network output
    for out in outs:
        for detection in out:
            scores = detection[5:]
            class_id = np.argmax(scores)
            confidence = scores[class_id]
            if confidence > 0.5:
                center_x = int(detection[0] * width)
                center_y = int(detection[1] * height)
                w = int(detection[2] * width)
                h = int(detection[3] * height)

                x = int(center_x - w / 2)
                y = int(center_y - h / 2)

                boxes.append([x, y, w, h])
                confidences.append(float(confidence))
                class_ids.append(class_id)

    # Apply Non-Maximum Suppression
    indexes = cv2.dnn.NMSBoxes(boxes, confidences, 0.5, 0.4)

    # Draw bounding boxes and labels
    for i in range(len(boxes)):
        if i in indexes:
            x, y, w, h = boxes[i]
            label = str(classes[class_ids[i]])
            detected_labels.append(label)  # Store detected label
            color = (0, 255, 0)  # Green color for bounding box
            cv2.rectangle(img, (x, y), (x + w, y + h), color, 2)

            # Adjust y-coordinate to ensure text visibility
            label_y = y - 10 if y - 10 > 10 else y + 20
            cv2.putText(img, label, (x, label_y), cv2.FONT_HERSHEY_SIMPLEX, 0.8, color, 2)

    # Save the result image in the uploads folder
    result_image_path = os.path.join(app.config['UPLOAD_FOLDER'], 'result.jpg')
    cv2.imwrite(result_image_path, img)

    return result_image_path, detected_labels

# Load the emotion recognition model and tokenizer
model = AutoModelForSequenceClassification.from_pretrained(r"C:\Users\monis\Downloads\Emotion_Text_Recognition-20240908T120131Z-001\Emotion_Text_Recognition")
tokenizer = AutoTokenizer.from_pretrained(r'C:\Users\monis\Downloads\Emotion_Text_Recognition-20240908T120131Z-001\Emotion_Text_Recognition')

# Define emotion labels
labels = ['sadness', 'joy', 'love', 'anger', 'fear', 'surprise']

# Function to preprocess the input text
def preprocess_text(text):
    return tokenizer(text, padding=True, truncation=True, return_tensors="pt")

# Home page for both functionalities
@app.route('/')
def index():
    return render_template('index.html')

# Route for emotion prediction
@app.route('/predict', methods=['POST'])
def predict_emotion():
    try:
        # Get the text from the form
        input_text = request.form.get('text', '')
        if not input_text:
            return jsonify({'error': 'No text provided'}), 400

        # Tokenize input text
        inputs = preprocess_text(input_text)

        # Ensure model is in evaluation mode
        model.eval()

        # Perform prediction
        with torch.no_grad():
            outputs = model(**inputs)

        # Get predicted class indices
        predictions = outputs.logits.argmax(dim=-1).cpu().numpy()
        predicted_label = labels[predictions[0]]

        return jsonify({'prediction': predicted_label})

    except Exception as e:
        return jsonify({'error': str(e)}), 400

# Route to handle image upload and object detection
@app.route('/upload', methods=['POST'])
def upload_image():
    if 'file' not in request.files:
        return jsonify({'error': 'No file part in the request'}), 400
    
    file = request.files['file']
    
    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400
    
    if file and allowed_file(file.filename):
        filename = secure_filename(file.filename)
        file_path = os.path.join(app.config['UPLOAD_FOLDER'], filename)
        file.save(file_path)

        # Run object detection
        result_image_path, detected_labels = detect_objects(file_path)

        # Return detected labels as JSON
        print(detected_labels)
        return jsonify({'labels': detected_labels,"filepath":file_path}), 200

    return jsonify({'error': 'Invalid file type'}), 400

if __name__ == '__main__':
    if not os.path.exists(UPLOAD_FOLDER):
        os.makedirs(UPLOAD_FOLDER)
    app.run(host='0.0.0.0', port=5000)
  