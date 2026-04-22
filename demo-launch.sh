#!/usr/bin/env bash
# SecureCAT-v2 — One-Shot Demo Launch
# Run from the project root: bash demo-launch.sh
#
# What it does (in order):
#   1. Builds frontend (npm run build)
#   2. Seeds demo data (php artisan demo:setup)
#   3. Starts Laravel server on port 8000
#   4. Starts queue listener
#   5. Starts ngrok tunnel + reads public URL
#   6. Serves presentation HTML on port 9090
#   7. Opens presentation in browser (Slide 6 has the ngrok URL + QR)
#
# Requirements: php, npm, ngrok, python (Python 3)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

APP_PORT=8000
SLIDE_PORT=9090
PID_FILE=".demo-pids"

# ── Cleanup on exit ────────────────────────────────────────────────────────────
cleanup() {
  echo ""
  echo "Stopping all demo services..."
  if [ -f "$PID_FILE" ]; then
    while IFS= read -r pid; do
      kill "$pid" 2>/dev/null || true
    done < "$PID_FILE"
    rm -f "$PID_FILE"
  fi
  echo "Done. Goodbye."
}
trap cleanup EXIT INT TERM

# ── Kill any lingering processes from previous runs ────────────────────────────
echo "Cleaning up old processes..."
rm -f "$PID_FILE"
taskkill //F //IM ngrok.exe 2>/dev/null   || pkill -f ngrok           2>/dev/null || true
pkill -f "php artisan serve"              2>/dev/null || true
pkill -f "php artisan queue"              2>/dev/null || true
pkill -f "http.server.*$SLIDE_PORT"       2>/dev/null || true
sleep 1

# ── Preflight checks ───────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════╗"
echo "║  SecureCAT-v2  —  Demo Launch            ║"
echo "╚══════════════════════════════════════════╝"
echo ""

for cmd in php npm ngrok python; do
  if ! command -v "$cmd" &>/dev/null; then
    echo "ERROR: '$cmd' not found in PATH."
    case $cmd in
      ngrok)  echo "  → Install from https://ngrok.com/download and add to PATH" ;;
      python) echo "  → Install Python 3 from https://python.org" ;;
    esac
    exit 1
  fi
done

# ── Step 1: Build frontend ─────────────────────────────────────────────────────
echo "[1/6] Building frontend assets (npm run build)..."
npm run build
echo "      ✓ Frontend built"

# ── Step 2: Seed demo data ─────────────────────────────────────────────────────
echo "[2/6] Seeding demo database (php artisan demo:setup)..."
php artisan demo:setup
echo "      ✓ Demo data ready"

# ── Step 3: Laravel server ─────────────────────────────────────────────────────
echo "[3/6] Starting Laravel server on port $APP_PORT..."
php artisan serve --port=$APP_PORT >/tmp/demo-serve.log 2>&1 &
echo $! >> "$PID_FILE"
sleep 2
echo "      ✓ Laravel running at http://localhost:$APP_PORT"

# ── Step 4: Queue listener ─────────────────────────────────────────────────────
echo "[4/6] Starting queue listener..."
php artisan queue:listen --timeout=60 >/tmp/demo-queue.log 2>&1 &
echo $! >> "$PID_FILE"
echo "      ✓ Queue listener running"

# ── Step 5: ngrok tunnels ─────────────────────────────────────────────────────
MAILPIT_PORT=8025
echo "[5/6] Starting ngrok tunnels..."
ngrok http "$APP_PORT" --log=stdout >/tmp/demo-ngrok.log 2>&1 &
echo $! >> "$PID_FILE"

ngrok http "$MAILPIT_PORT" --log=stdout >/tmp/demo-ngrok-mailpit.log 2>&1 &
echo $! >> "$PID_FILE"

echo "      Waiting for ngrok to initialize (up to 20 s)..."
NGROK_URL=""
for i in $(seq 1 20); do
  sleep 1
  NGROK_URL=$(curl -s http://localhost:4040/api/tunnels 2>/dev/null \
    | grep -o '"public_url":"https://[^"]*' \
    | head -1 \
    | sed 's/"public_url":"//') || true
  if [ -n "$NGROK_URL" ]; then
    break
  fi
done

if [ -z "$NGROK_URL" ]; then
  echo ""
  echo "  WARNING: Could not read ngrok URL automatically."
  echo "  → Open http://localhost:4040 to find the tunnel URL"
  echo "  → Then open the slide manually with ?url=<YOUR_NGROK_URL>"
  NGROK_URL="http://localhost:$APP_PORT"
fi
echo "      ✓ Public URL: $NGROK_URL"

NGROK_MAILPIT_URL=""
for i in $(seq 1 20); do
  sleep 1
  NGROK_MAILPIT_URL=$(curl -s http://localhost:4041/api/tunnels 2>/dev/null \
    | grep -o '"public_url":"https://[^"]*' \
    | head -1 \
    | sed 's/"public_url":"//') || true
  if [ -n "$NGROK_MAILPIT_URL" ]; then
    break
  fi
done

if [ -z "$NGROK_MAILPIT_URL" ]; then
  echo "  WARNING: Could not read mailpit ngrok URL."
  echo "  → Open http://localhost:4041 to find the tunnel URL"
  NGROK_MAILPIT_URL="http://localhost:$MAILPIT_PORT"
fi
echo "      ✓ Mailpit URL: $NGROK_MAILPIT_URL"

# ── Step 6: Serve presentation slides ─────────────────────────────────────────
echo "[6/6] Serving presentation slides on port $SLIDE_PORT..."
python -m http.server "$SLIDE_PORT" >/tmp/demo-slides.log 2>&1 &
echo $! >> "$PID_FILE"
sleep 1

# Encode the ngrok URL for use as a query parameter
ENCODED_URL="$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1],safe=''))" "$NGROK_URL" 2>/dev/null \
  || python  -c "import urllib,sys;  print(urllib.quote(sys.argv[1],safe=''))"              "$NGROK_URL" 2>/dev/null \
  || echo "$NGROK_URL")"

SLIDE_URL="http://localhost:${SLIDE_PORT}/presentation-securecat.html?url=${ENCODED_URL}"

echo "      ✓ Slides at http://localhost:$SLIDE_PORT"

# ── Open presentation in browser ───────────────────────────────────────────────
echo ""
echo "Opening presentation in browser..."
if cmd //c start "$SLIDE_URL" 2>/dev/null; then
  true
elif open "$SLIDE_URL" 2>/dev/null; then
  true
elif xdg-open "$SLIDE_URL" 2>/dev/null; then
  true
else
  echo "  → Could not open browser automatically."
  echo "  → Open manually: $SLIDE_URL"
fi

# ── Ready banner ───────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         ALL SYSTEMS GO  —  SecureCAT Demo is Live           ║"
echo "╠══════════════════════════════════════════════════════════════╣"
printf "║  App (local):    http://localhost:%-26s║\n" "$APP_PORT  "
printf "║  App (public):   %-43s║\n" "$NGROK_URL"
printf "║  Mailpit:        %-43s║\n" "$NGROK_MAILPIT_URL"
printf "║  Slides:         http://localhost:%-26s║\n" "$SLIDE_PORT  "
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║  NEXT STEPS:                                                 ║"
echo "║   1. Presentation opened → navigate to Slide 5 (Intro)      ║"
echo "║   2. Browser A → log in as staff/admin (see checklist)       ║"
echo "║   3. Browser B → log in as applicant (see checklist)         ║"
echo "║   4. Follow Demo-template.md for the stage script            ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║  Logs:                                                       ║"
echo "║    /tmp/demo-serve.log   /tmp/demo-queue.log                 ║"
echo "║    /tmp/demo-ngrok.log   /tmp/demo-ngrok-mailpit.log        ║"
echo "║    /tmp/demo-slides.log                                     ║"
echo "╠══════════════════════════════════════════════════════════════╣"
echo "║  Press Ctrl+C to stop all services when done.                ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

wait
