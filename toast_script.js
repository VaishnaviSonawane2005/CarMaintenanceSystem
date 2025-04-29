// toast_script.js
function createToast(type, message, duration = 3000) {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast', type);
    toast.innerText = message;
    toastContainer.appendChild(toast);
    
    // Remove toast after the duration
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 500); // Delay for hiding
    }, duration);
}
