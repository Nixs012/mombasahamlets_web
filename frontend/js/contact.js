document.addEventListener('DOMContentLoaded', async () => {
    const apiUrl = window.API_URL || 'http://localhost/mombasahamlets_web/backend/api';

    // Fetch Contact Details (Settings)
    try {
        const res = await fetch(`${apiUrl}/settings.php`);
        const settings = await res.json();

        // Map elements using robust IDs
        const addressEl = document.querySelector('#contact-address p');
        const phoneEl = document.querySelector('#contact-phone p');
        const emailEl = document.querySelector('#contact-email p');
        const hoursEl = document.querySelector('#contact-hours p');

        if (addressEl && settings.contact_stadium_address) {
            addressEl.innerHTML = settings.contact_stadium_address.replace(/\n/g, '<br>');
        }
        if (phoneEl) {
            phoneEl.innerHTML = `Main Office: ${settings.contact_office_phone || '+254 723 456 789'}<br>Ticketing: ${settings.contact_ticketing_phone || '+254 700 123 456'}<br>Academy: ${settings.contact_academy_phone || '+254 711 987 654'}`;
        }
        if (emailEl) {
            emailEl.innerHTML = `General: ${settings.contact_general_email || 'info@mombasahamletsfc.co.ke'}<br>Ticket Sales: ${settings.contact_tickets_email || 'tickets@mombasahamletsfc.co.ke'}<br>Partnerships: ${settings.contact_partnerships_email || 'partnerships@mombasahamletsfc.co.ke'}`;
        }
        if (hoursEl && settings.contact_office_hours) {
            hoursEl.innerHTML = settings.contact_office_hours.replace(/\n/g, '<br>');
        }

    } catch (e) { console.error('Error loading contact settings:', e); }

    // Fetch FAQs
    try {
        const res = await fetch(`${apiUrl}/faqs.php`);
        const items = await res.json();
        const faqSection = document.querySelector('.faq-section');
        if (faqSection && items.length > 0) {
            // Keep the header
            const h2 = faqSection.querySelector('h2');
            let html = `<h2>${h2.textContent}</h2>`;

            items.forEach(item => {
                html += `
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>${item.question}</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>${item.answer}</p>
                        </div>
                    </div>
                `;
            });
            faqSection.innerHTML = html;

            setupFAQToggle();
        }
    } catch (e) { console.error('Error loading FAQs:', e); }

    // Initialize Toggle
    setupFAQToggle();

    function setupFAQToggle() {
        const faqItems = document.querySelectorAll('.faq-item');

        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            if (question) {
                // Remove any existing listeners by cloning (optional but safe)
                const newQuestion = question.cloneNode(true);
                question.parentNode.replaceChild(newQuestion, question);

                newQuestion.addEventListener('click', () => {
                    const answer = item.querySelector('.faq-answer');
                    const icon = newQuestion.querySelector('i');
                    const isActive = item.classList.toggle('active');

                    if (answer) {
                        // Use inline max-height for identifying the transition
                        if (isActive) {
                            answer.style.maxHeight = answer.scrollHeight + "px";
                            answer.style.padding = "1.2rem"; // Ensure padding is applied if missing from CSS
                        } else {
                            answer.style.maxHeight = null;
                            answer.style.padding = null; // Revert
                        }
                    }

                    if (icon) {
                        // Force icon rotation via JS if CSS fails
                        icon.style.transform = isActive ? 'rotate(180deg)' : 'rotate(0deg)';
                        icon.style.transition = 'transform 0.3s ease';
                    }
                });
            }
        });
    }
});
