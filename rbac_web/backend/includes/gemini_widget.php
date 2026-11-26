
<?php
if (empty($_SESSION['gemini_csrf'])) {
    $_SESSION['gemini_csrf'] = bin2hex(random_bytes(32));
}
?>

<div class="gemini-widget" id="geminiWidget">

    <button type="button"
            class="gemini-toggle"
            id="geminiToggle">
        ✦ Gemini AI
    </button>

    <section class="gemini-panel"
             id="geminiPanel"
             hidden>

        <div class="gemini-header">
            <div class="gemini-brand">
                <span class="gemini-logo">✦</span>
                <div>
                    <strong>Gemini AI Assistant</strong>
                    <small>Powered by Google Gemini</small>
                </div>
            </div>

            <button type="button"
                    id="geminiClose"
                    aria-label="Close chatbot">
                &times;
            </button>
        </div>

        <div class="gemini-messages"
             id="geminiMessages"
             aria-live="polite">

            <div class="gemini-message bot">
                Hello! I'm Gemini AI Assistant.
                How can I help you with the RBAC system?
            </div>

        </div>

        <form class="gemini-form" id="geminiForm">

            <input type="hidden"
                   id="geminiCsrf"
                   value="<?= htmlspecialchars(
                       $_SESSION['gemini_csrf'],
                       ENT_QUOTES,
                       'UTF-8'
                   ) ?>">

            <input type="text"
                   id="geminiInput"
                   maxlength="1000"
                   placeholder="Ask Gemini anything..."
                   autocomplete="off"
                   required>

            <button type="submit" id="geminiSend">
                Send
            </button>
        </form>

    </section>

</div>
