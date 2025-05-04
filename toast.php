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
@keyframes slideIn { 
    0% { transform: translateX(100%) scale(0.9); opacity: 0; } 
    100% { transform: translateX(0) scale(1); opacity: 1; } 
}
@keyframes slideOut { 
    0% { transform: translateX(0) scale(1); opacity: 1; } 
    100% { transform: translateX(100%) scale(0.9); opacity: 0; } 
}
@keyframes pulse { 
    0%, 100% { transform: scale(1); } 
    50% { transform: scale(1.1); } 
}
@keyframes progressBar { 
    from { width: 100%; } 
    to { width: 0; } 
}

.toast {
    background: #ffffff;
    color: #333;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    max-width: 350px;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    animation: slideIn 0.5s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(6px);
    transform-origin: right center;
}
body.dark-mode .toast {
    background: #1c1c1e;
    color: #f2f2f2;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.toast-icon { 
    font-size: 22px;
    flex-shrink: 0;
}
.toast-icon.success {
    color: #2ecc71;
    animation: pulse 1.5s infinite;
}
.toast-icon.error {
    color: #e74c3c;
}
.toast-progress { 
    position: absolute; 
    bottom: 0; 
    left: 0; 
    height: 4px; 
    background: linear-gradient(90deg, #00c851, #007e33); 
    animation: progressBar 3s linear forwards; 
}
.toast-progress.error {
    background: linear-gradient(90deg, #ff4444, #cc0000);
}
.toast-close {
    margin-left: auto;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}
.toast-close:hover {
    opacity: 1;
}
</style>

<script>
function createToast(type, message, duration = 3000) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    const isDarkMode = document.body.classList.contains('dark-mode');

    toast.className = `toast ${isDarkMode ? 'dark' : ''}`;
    toast.innerHTML = `
        <div class="toast-icon ${type}">${type === 'success' ? '✓' : '✗'}</div>
        <div>${message}</div>
        <div class="toast-close">&times;</div>
        <div class="toast-progress ${type === 'error' ? 'error' : ''}"></div>
    `;

    container.appendChild(toast);

    // Play sound
    const sound = document.getElementById(`toast-${type}`);
    if (sound) { 
        sound.currentTime = 0; 
        sound.play().catch(e => console.log('Audio play failed:', e)); 
    }

    // Close button functionality
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.style.animation = 'slideOut 0.5s ease forwards';
        setTimeout(() => toast.remove(), 500);
    });

    // Auto-dismiss
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.5s ease forwards';
        setTimeout(() => toast.remove(), 500);
    }, duration);
}
</script>