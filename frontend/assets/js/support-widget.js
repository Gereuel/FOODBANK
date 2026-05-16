(function () {
    const root = document.getElementById('support-widget');
    if (!root || root.dataset.ready === 'true') return;
    root.dataset.ready = 'true';

    const apiBase = '/foodbank/backend/api/support';
    const messageBase = '/foodbank/backend/api/messages';
    const launcher = document.getElementById('support-launcher');
    const panel = document.getElementById('support-panel');
    const closeBtn = document.getElementById('support-close');
    const tabs = root.querySelectorAll('.support-tab');
    const views = root.querySelectorAll('.support-view');
    const chatLog = document.getElementById('support-chat-log');
    const chatForm = document.getElementById('support-chat-form');
    const chatInput = document.getElementById('support-chat-input');
    const ticketForm = document.getElementById('support-ticket-form');
    const ticketStatus = document.getElementById('support-form-status');
    const ticketList = document.getElementById('support-ticket-list');
    const ticketDetail = document.getElementById('support-ticket-detail');
    let adminContact = null;
    let chatLoaded = false;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open && !chatLoaded) loadAdminChat();
    }

    function setTab(name) {
        tabs.forEach(tab => tab.classList.toggle('is-active', tab.dataset.supportTab === name));
        views.forEach(view => view.classList.toggle('is-active', view.dataset.supportView === name));
        if (name === 'chat' && !chatLoaded) loadAdminChat();
        if (name === 'tickets') loadTickets();
    }

    function renderMessage(message) {
        return `
            <div class="support-message ${message.is_mine ? 'is-mine' : ''}">
                <p>${escapeHtml(message.body)}</p>
                <span>${escapeHtml(message.time_label || '')}</span>
            </div>
        `;
    }

    function scrollChat() {
        chatLog.scrollTop = chatLog.scrollHeight;
    }

    function loadAdminChat() {
        chatLoaded = true;
        chatLog.innerHTML = '<div class="support-empty">Loading admin chat...</div>';

        fetch(`${apiBase}/get_admin_contact.php`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to find admin support');
                adminContact = data.contact;
                return fetch(`${messageBase}/get_messages.php?contact_id=${encodeURIComponent(adminContact.account_id)}`);
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load admin chat');
                chatLog.innerHTML = data.messages.length
                    ? data.messages.map(renderMessage).join('')
                    : '<div class="support-empty">No messages yet. Start a conversation with admin.</div>';
                scrollChat();
            })
            .catch(error => {
                chatLoaded = false;
                chatLog.innerHTML = `<div class="support-empty">${escapeHtml(error.message)}</div>`;
            });
    }

    function sendSupportMessage(body) {
        if (!adminContact || !body.trim()) return;

        const formData = new FormData();
        formData.append('contact_id', adminContact.account_id);
        formData.append('body', body.trim());

        fetch(`${messageBase}/send_message.php`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to send message');
                if (chatLog.querySelector('.support-empty')) chatLog.innerHTML = '';
                chatLog.insertAdjacentHTML('beforeend', renderMessage(data.message));
                chatInput.value = '';
                scrollChat();
            })
            .catch(error => alert(error.message));
    }

    function renderTicketItem(ticket) {
        return `
            <button class="support-ticket-item" type="button" data-ticket-id="${ticket.ticket_id}">
                <span>
                    <strong>${escapeHtml(ticket.subject)}</strong><br>
                    <small>${escapeHtml(ticket.category)} · ${escapeHtml(ticket.updated_label || '')}</small>
                </span>
                <span class="support-badge">${escapeHtml(ticket.status)}</span>
            </button>
        `;
    }

    function loadTickets() {
        ticketDetail.hidden = true;
        ticketList.innerHTML = '<div class="support-empty">Loading tickets...</div>';
        fetch(`${apiBase}/list_tickets.php`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load tickets');
                ticketList.innerHTML = data.tickets.length
                    ? data.tickets.map(renderTicketItem).join('')
                    : '<div class="support-empty">No tickets submitted yet.</div>';
            })
            .catch(error => {
                ticketList.innerHTML = `<div class="support-empty">${escapeHtml(error.message)}</div>`;
            });
    }

    function renderTicketDetail(data) {
        const replies = data.replies.length
            ? data.replies.map(reply => `
                <div class="support-reply">
                    <strong>${escapeHtml(reply.sender_name)}</strong>
                    <small>${escapeHtml(reply.created_label || '')}</small>
                    <p>${escapeHtml(reply.body)}</p>
                </div>
            `).join('')
            : '<div class="support-empty">No replies yet.</div>';

        ticketDetail.innerHTML = `
            <h3>${escapeHtml(data.ticket.subject)}</h3>
            <small>${escapeHtml(data.ticket.category)} · ${escapeHtml(data.ticket.status)} · ${escapeHtml(data.ticket.created_label)}</small>
            <p>${escapeHtml(data.ticket.description)}</p>
            <div class="support-replies">${replies}</div>
            <form class="support-reply-form" data-reply-ticket-id="${data.ticket.ticket_id}">
                <label>
                    <span>Reply</span>
                    <textarea name="body" maxlength="5000" required></textarea>
                </label>
                <button type="submit" class="support-primary-btn">Send Reply</button>
            </form>
        `;
        ticketDetail.hidden = false;
    }

    function openTicket(ticketId) {
        fetch(`${apiBase}/get_ticket.php?ticket_id=${encodeURIComponent(ticketId)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load ticket');
                renderTicketDetail(data);
            })
            .catch(error => alert(error.message));
    }

    launcher.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
    closeBtn.addEventListener('click', () => setOpen(false));
    tabs.forEach(tab => tab.addEventListener('click', () => setTab(tab.dataset.supportTab)));

    chatForm.addEventListener('submit', event => {
        event.preventDefault();
        sendSupportMessage(chatInput.value);
    });

    ticketForm.addEventListener('submit', event => {
        event.preventDefault();
        ticketStatus.textContent = '';
        ticketStatus.classList.remove('is-error');

        fetch(`${apiBase}/create_ticket.php`, {
            method: 'POST',
            body: new FormData(ticketForm)
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to submit ticket');
                ticketForm.reset();
                ticketStatus.textContent = `Ticket #${data.ticket_id} submitted.`;
                setTab('tickets');
            })
            .catch(error => {
                ticketStatus.textContent = error.message;
                ticketStatus.classList.add('is-error');
            });
    });

    ticketList.addEventListener('click', event => {
        const item = event.target.closest('.support-ticket-item[data-ticket-id]');
        if (item) openTicket(item.dataset.ticketId);
    });

    ticketDetail.addEventListener('submit', event => {
        const form = event.target.closest('.support-reply-form');
        if (!form) return;
        event.preventDefault();

        const formData = new FormData(form);
        formData.append('ticket_id', form.dataset.replyTicketId);

        fetch(`${apiBase}/reply_ticket.php`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to send reply');
                openTicket(form.dataset.replyTicketId);
                loadTickets();
            })
            .catch(error => alert(error.message));
    });
})();
