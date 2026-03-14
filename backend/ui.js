export function initUI() {
    // Handle form cancellations
    document.querySelectorAll('.btn-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            if (form) {
                form.reset();
                delete form.dataset.editingId;
                
                // Reset form titles
                const formTitle = form.querySelector('h2, h3');
                if (formTitle) {
                    const originalText = formTitle.dataset.originalText || formTitle.textContent;
                    formTitle.textContent = originalText;
                }
                
                // Clear image previews
                form.querySelectorAll('.image-input').forEach(input => {
                    const preview = input.parentNode.querySelector('img');
                    if (preview) {
                        preview.src = '';
                        preview.style.display = 'none';
                    }
                });
            }
        });
    });
    
    // Store original form titles
    document.querySelectorAll('form h2, form h3').forEach(title => {
        if (!title.dataset.originalText) {
            title.dataset.originalText = title.textContent;
        }
    });
}
