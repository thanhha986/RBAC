
document.addEventListener('DOMContentLoaded', () => {

    const toggle = document.getElementById('geminiToggle');
    const panel = document.getElementById('geminiPanel');
    const close = document.getElementById('geminiClose');

    const form = document.getElementById('geminiForm');
    const input = document.getElementById('geminiInput');
    const send = document.getElementById('geminiSend');
    const messages = document.getElementById('geminiMessages');
    const csrf = document.getElementById('geminiCsrf');

    if (!toggle || !panel || !form) return;

    // Open and close Gemini chatbot
    toggle.addEventListener('click', () => {
        panel.hidden = !panel.hidden;

        if (!panel.hidden) {
            input.focus();
        }
    });

    close.addEventListener('click', () => {
        panel.hidden = true;
    });

    // Display messages safely
    function addMessage(text, type) {
        const element = document.createElement('div');

        element.className = 'gemini-message ' + type;
        element.textContent = text;

        messages.appendChild(element);
        messages.scrollTop = messages.scrollHeight;

        return element;
    }

    // Send message to Gemini API
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const message = input.value.trim();

        if (!message) return;

        addMessage(message, 'user');

        input.value = '';
        input.disabled = true;
        send.disabled = true;

        const loading = addMessage(
            'Gemini is thinking...',
            'bot'
        );

        try {
            const response = await fetch(
                '/rbac_web/api/gemini_chat.php',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        message: message,
                        csrf_token: csrf.value
                    })
                }
            );

            const data = await response.json();

            loading.remove();

            if (!response.ok) {
                throw new Error(
                    data.error || 'Something went wrong.'
                );
            }

            addMessage(data.reply, 'bot');

        } catch (error) {
            loading.remove();

            addMessage(
                error.message || 'Unable to contact Gemini.',
                'bot'
            );

        } finally {
            input.disabled = false;
            send.disabled = false;
            input.focus();
        }
    });

});
