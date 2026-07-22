#!/bin/bash
# Installs quizaccess_edproctoring face recognition service as a systemd unit.
# Run as root: sudo bash install.sh

set -e

SERVICE_NAME="edproctoring-face"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON_BIN=$(which python3)

echo "Installing $SERVICE_NAME systemd service..."
echo "Service directory: $SCRIPT_DIR"

# Install Python dependencies.
$PYTHON_BIN -m pip install -r "$SCRIPT_DIR/requirements.txt" --quiet

# Create systemd unit file.
cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=EDP Face Recognition Service (quizaccess_edproctoring)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$SCRIPT_DIR
ExecStart=$PYTHON_BIN -m uvicorn main:app --host 127.0.0.1 --port 8765 --workers 1
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
echo "Systemd service installed at $SERVICE_FILE"
echo ""
echo "Run the following to enable and start:"
echo "  sudo systemctl enable $SERVICE_NAME"
echo "  sudo systemctl start $SERVICE_NAME"
echo "  sudo systemctl status $SERVICE_NAME"
echo ""
echo "Verify: curl http://localhost:8765/health"
