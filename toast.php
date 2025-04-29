<!-- Toast Container -->
<div id="toast-container" style="
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    z-index: 9999;
    pointer-events: none;
"></div>

<!-- Toast Sounds -->
<audio id="toast-success" src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" preload="auto"></audio>
<audio id="toast-error" src="https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3" preload="auto"></audio>

<style>
@keyframes slideIn { 0% { transform: translateX(100%) scale(0.9); opacity: 0; } 100% { transform: translateX(0) scale(1); opacity: 1; } }
@keyframes slideOut { 0% { transform: translateX(0) scale(1); opacity: 1; } 100% { transform: translateX(100%) scale(0.9); opacity: 0; } }
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
@keyframes progressBar { from { width: 100%; } to { width: 0; } }

.toast {
    background: #ffffff;
    color: #333;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    max-width: 350px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    animation: slideIn 0.5s ease forwards;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(6px);
}
.toast.dark { background: #1c1c1e; color: #f2f2f2; }
.toast-icon { font-size: 22px; }
.pulse { animation: pulse 1.5s infinite; display: inline-block; }
.toast-progress { position: absolute; bottom: 0; left: 0; height: 4px; background: linear-gradient(90deg, #00c851, #007e33); animation: progressBar 3s linear forwards; }
</style>

<script>
function createToast(type, message, duration = 3000) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    toast.className = 'toast' + (prefersDark ? ' dark' : '');
    toast.innerHTML = `
        <div class="toast-icon ${type === 'success' ? 'pulse' : ''}">${type === 'success' ? '✅' : '❌'}</div>
        <div>${message}</div>
        <div class="toast-progress" style="background: ${type === 'success' ? 'linear-gradient(90deg, #00c851, #007e33)' : 'linear-gradient(90deg, #ff4444, #cc0000)'};"></div>
    `;

    container.appendChild(toast);

    const sound = document.getElementById(type === 'success' ? 'toast-success' : 'toast-error');
    if (sound) { sound.currentTime = 0; sound.play(); }

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.5s ease forwards';
        setTimeout(() => container.removeChild(toast), 500);
    }, duration);
}
</script>
