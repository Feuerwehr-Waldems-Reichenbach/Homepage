document.addEventListener('DOMContentLoaded', function() {
    // Fade-in und Slide-up Animationen für Container-Elemente
    const errorContainer = document.querySelector('.error-container');
    errorContainer.classList.add('fade-in');
    
    const errorElements = document.querySelectorAll('.error-code, .error-title, .error-message, .buttons-container');
    errorElements.forEach((element, index) => {
        setTimeout(() => {
            element.classList.add('slide-up');
        }, 100 * (index + 1));
    });
    
    // Bounce-Animation für den Error-Code
    const errorCode = document.querySelector('.error-code');
    setTimeout(() => {
        errorCode.classList.add('bounce');
    }, 800);
});

// Fügt einen Hover-Effekt zu allen Buttons hinzu
const buttons = document.querySelectorAll('.btn');
buttons.forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.classList.add('bounce');
        setTimeout(() => {
            this.classList.remove('bounce');
        }, 600);
    });
}); 