<div class="support-widget" id="support-widget">
    <button class="support-launcher" type="button" id="support-launcher" aria-label="Open help and support">
        <i class="far fa-message"></i>
        <span>Help</span>
    </button>

    <section class="support-panel" id="support-panel" aria-hidden="true" aria-labelledby="support-title">
        <header class="support-panel-header">
            <div>
                <h2 id="support-title">Help &amp; Support</h2>
                <p>Chat with admin or submit a report ticket.</p>
            </div>
            <button class="support-icon-btn" type="button" id="support-close" aria-label="Close support">
                <i class="fas fa-xmark"></i>
            </button>
        </header>

        <div class="support-tabs" role="tablist" aria-label="Support options">
            <button type="button" class="support-tab is-active" data-support-tab="chat">Chat Admin</button>
            <button type="button" class="support-tab" data-support-tab="ticket">Submit Ticket</button>
            <button type="button" class="support-tab" data-support-tab="tickets">Tickets</button>
        </div>

        <div class="support-view is-active" data-support-view="chat">
            <div class="support-chat-log" id="support-chat-log">
                <div class="support-empty">Loading admin chat...</div>
            </div>
            <form class="support-chat-form" id="support-chat-form">
                <input type="text" id="support-chat-input" maxlength="2000" placeholder="Write a message to admin" aria-label="Write a message to admin">
                <button type="submit" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>

        <form class="support-view support-ticket-form" data-support-view="ticket" id="support-ticket-form">
            <label>
                <span>Category</span>
                <select name="category">
                    <option value="Account">Account</option>
                    <option value="Donation">Donation</option>
                    <option value="Food Bank">Food Bank</option>
                    <option value="Technical">Technical</option>
                    <option value="Report">Report</option>
                    <option value="Other">Other</option>
                </select>
            </label>
            <label>
                <span>Priority</span>
                <select name="priority">
                    <option value="Normal">Normal</option>
                    <option value="Low">Low</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                </select>
            </label>
            <label class="support-field-full">
                <span>Subject</span>
                <input type="text" name="subject" maxlength="160" required>
            </label>
            <label class="support-field-full">
                <span>Details</span>
                <textarea name="description" rows="5" maxlength="5000" required></textarea>
            </label>
            <p class="support-form-status" id="support-form-status" aria-live="polite"></p>
            <button type="submit" class="support-primary-btn">Submit Ticket</button>
        </form>

        <div class="support-view" data-support-view="tickets">
            <div class="support-ticket-list" id="support-ticket-list"></div>
            <div class="support-ticket-detail" id="support-ticket-detail" hidden></div>
        </div>
    </section>
</div>
