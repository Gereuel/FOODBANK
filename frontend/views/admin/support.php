<?php
session_start();

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'AA') {
    http_response_code(401);
    exit('Unauthorized');
}
?>

<section class="support-admin" id="support-admin">
    <header class="support-admin-header">
        <div>
            <h1>Support Inbox</h1>
            <p>Handle direct admin chats and reported support tickets from PA and FA accounts.</p>
        </div>
        <div class="support-admin-tabs" role="tablist" aria-label="Support inbox views">
            <button type="button" class="support-admin-tab is-active" data-admin-support-tab="tickets">Tickets</button>
            <button type="button" class="support-admin-tab" data-admin-support-tab="chats">Chats</button>
        </div>
    </header>

    <div class="support-admin-view is-active" data-admin-support-view="tickets">
        <div class="support-admin-grid">
            <div class="support-admin-list" id="admin-ticket-list"></div>
            <div class="support-admin-detail" id="admin-ticket-detail">
                <div class="support-empty">Select a ticket to view details.</div>
            </div>
        </div>
    </div>

    <div class="support-admin-view" data-admin-support-view="chats">
        <div class="support-admin-grid">
            <div class="support-admin-list" id="admin-chat-list"></div>
            <div class="support-admin-detail" id="admin-chat-detail">
                <div class="support-empty">Select a chat to view messages.</div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    const root = document.getElementById('support-admin');
    if (!root || root.dataset.ready === 'true') return;
    root.dataset.ready = 'true';

    const supportApi = '/foodbank/backend/api/support';
    const messageApi = '/foodbank/backend/api/messages';
    const tabs = root.querySelectorAll('.support-admin-tab');
    const views = root.querySelectorAll('.support-admin-view');
    const ticketList = document.getElementById('admin-ticket-list');
    const ticketDetail = document.getElementById('admin-ticket-detail');
    const chatList = document.getElementById('admin-chat-list');
    const chatDetail = document.getElementById('admin-chat-detail');
    let activeChatContact = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function setView(name) {
        tabs.forEach(tab => tab.classList.toggle('is-active', tab.dataset.adminSupportTab === name));
        views.forEach(view => view.classList.toggle('is-active', view.dataset.adminSupportView === name));
        if (name === 'tickets') loadTickets();
        if (name === 'chats') loadChats();
    }

    function ticketItem(ticket) {
        return `
            <button class="support-admin-item" type="button" data-ticket-id="${ticket.ticket_id}">
                <span>
                    <strong>${escapeHtml(ticket.subject)}</strong>
                    <span>${escapeHtml(ticket.reporter_name)} · ${escapeHtml(ticket.category)}</span>
                    <small>${escapeHtml(ticket.updated_label || '')}</small>
                </span>
                <span class="support-admin-badge">${escapeHtml(ticket.status)}</span>
            </button>
        `;
    }

    function loadTickets() {
        ticketList.innerHTML = '<div class="support-empty">Loading tickets...</div>';
        fetch(`${supportApi}/list_tickets.php`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load tickets');
                ticketList.innerHTML = data.tickets.length
                    ? data.tickets.map(ticketItem).join('')
                    : '<div class="support-empty">No support tickets yet.</div>';
            })
            .catch(error => {
                ticketList.innerHTML = `<div class="support-empty">${escapeHtml(error.message)}</div>`;
            });
    }

    function renderTicket(data) {
        const replies = data.replies.length
            ? data.replies.map(reply => `
                <div class="support-ticket-reply ${reply.is_mine ? 'is-mine' : ''}">
                    <p>${escapeHtml(reply.body)}</p>
                    <span>${escapeHtml(reply.sender_name)} · ${escapeHtml(reply.created_label || '')}</span>
                </div>
            `).join('')
            : '<div class="support-empty">No replies yet.</div>';

        ticketDetail.innerHTML = `
            <header class="support-detail-header">
                <div>
                    <h2>${escapeHtml(data.ticket.subject)}</h2>
                    <p>${escapeHtml(data.ticket.reporter_name)} · ${escapeHtml(data.ticket.category)} · ${escapeHtml(data.ticket.priority)}</p>
                    <small>Created ${escapeHtml(data.ticket.created_label || '')}</small>
                </div>
                <select class="support-status-select" data-ticket-status-id="${data.ticket.ticket_id}" aria-label="Ticket status">
                    ${['Open', 'In Progress', 'Resolved', 'Closed'].map(status => `<option value="${status}" ${status === data.ticket.status ? 'selected' : ''}>${status}</option>`).join('')}
                </select>
            </header>
            <div class="support-thread">
                <p class="support-ticket-description">${escapeHtml(data.ticket.description)}</p>
                ${replies}
            </div>
            <form class="support-composer" data-ticket-reply-id="${data.ticket.ticket_id}">
                <textarea name="body" maxlength="5000" placeholder="Reply to this ticket" aria-label="Reply to this ticket" required></textarea>
                <button type="submit" aria-label="Send reply"><i class="fas fa-paper-plane"></i></button>
            </form>
        `;
        const thread = ticketDetail.querySelector('.support-thread');
        thread.scrollTop = thread.scrollHeight;
    }

    function openTicket(ticketId) {
        fetch(`${supportApi}/get_ticket.php?ticket_id=${encodeURIComponent(ticketId)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load ticket');
                renderTicket(data);
            })
            .catch(error => alert(error.message));
    }

    function chatItem(conversation) {
        const contact = conversation.contact;
        const last = conversation.last_message || {};
        const preview = last.is_mine ? `You: ${last.body || ''}` : (last.body || '');
        return `
            <button class="support-admin-item" type="button" data-contact-id="${contact.account_id}">
                <span>
                    <strong>${escapeHtml(contact.name)}</strong>
                    <span>${escapeHtml(preview)}</span>
                    <small>${escapeHtml(last.time_label || '')}</small>
                </span>
                <span class="support-admin-badge">${escapeHtml(contact.account_type)}</span>
            </button>
        `;
    }

    function loadChats() {
        chatList.innerHTML = '<div class="support-empty">Loading chats...</div>';
        fetch(`${messageApi}/get_conversations.php`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load chats');
                chatList.innerHTML = data.conversations.length
                    ? data.conversations.map(chatItem).join('')
                    : '<div class="support-empty">No admin chats yet.</div>';
            })
            .catch(error => {
                chatList.innerHTML = `<div class="support-empty">${escapeHtml(error.message)}</div>`;
            });
    }

    function renderChatMessage(message) {
        return `
            <div class="support-message ${message.is_mine ? 'is-mine' : ''}">
                <p>${escapeHtml(message.body)}</p>
                <span>${escapeHtml(message.time_label || '')}</span>
            </div>
        `;
    }

    function openChat(contactId) {
        fetch(`${messageApi}/get_messages.php?contact_id=${encodeURIComponent(contactId)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load chat');
                activeChatContact = data.contact;
                chatDetail.innerHTML = `
                    <header class="support-detail-header">
                        <div>
                            <h2>${escapeHtml(data.contact.name)}</h2>
                            <p>${escapeHtml(data.contact.subtitle || '')}</p>
                        </div>
                    </header>
                    <div class="support-thread">
                        ${data.messages.length ? data.messages.map(renderChatMessage).join('') : '<div class="support-empty">No messages yet.</div>'}
                    </div>
                    <form class="support-composer" id="admin-chat-form">
                        <input type="text" name="body" maxlength="2000" placeholder="Write a message" aria-label="Write a message" required>
                        <button type="submit" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
                    </form>
                `;
                const thread = chatDetail.querySelector('.support-thread');
                thread.scrollTop = thread.scrollHeight;
            })
            .catch(error => alert(error.message));
    }

    function sendChatMessage(body) {
        if (!activeChatContact || !body.trim()) return;
        const formData = new FormData();
        formData.append('contact_id', activeChatContact.account_id);
        formData.append('body', body.trim());

        fetch(`${messageApi}/send_message.php`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to send message');
                openChat(activeChatContact.account_id);
                loadChats();
            })
            .catch(error => alert(error.message));
    }

    tabs.forEach(tab => tab.addEventListener('click', () => setView(tab.dataset.adminSupportTab)));
    ticketList.addEventListener('click', event => {
        const item = event.target.closest('.support-admin-item[data-ticket-id]');
        if (item) openTicket(item.dataset.ticketId);
    });
    chatList.addEventListener('click', event => {
        const item = event.target.closest('.support-admin-item[data-contact-id]');
        if (item) openChat(item.dataset.contactId);
    });
    ticketDetail.addEventListener('change', event => {
        const select = event.target.closest('.support-status-select[data-ticket-status-id]');
        if (!select) return;
        const formData = new FormData();
        formData.append('ticket_id', select.dataset.ticketStatusId);
        formData.append('status', select.value);
        fetch(`${supportApi}/update_ticket_status.php`, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to update status');
                openTicket(select.dataset.ticketStatusId);
                loadTickets();
            })
            .catch(error => alert(error.message));
    });
    ticketDetail.addEventListener('submit', event => {
        const form = event.target.closest('.support-composer[data-ticket-reply-id]');
        if (!form) return;
        event.preventDefault();
        const formData = new FormData(form);
        formData.append('ticket_id', form.dataset.ticketReplyId);
        fetch(`${supportApi}/reply_ticket.php`, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to send reply');
                openTicket(form.dataset.ticketReplyId);
                loadTickets();
            })
            .catch(error => alert(error.message));
    });
    chatDetail.addEventListener('submit', event => {
        const form = event.target.closest('#admin-chat-form');
        if (!form) return;
        event.preventDefault();
        sendChatMessage(new FormData(form).get('body'));
    });

    loadTickets();
})();
</script>
