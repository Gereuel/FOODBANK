<section class="messages-section" aria-labelledby="messages-title">
    <div class="messages-heading">
        <div>
            <h2 id="messages-title">Messages</h2>
            <p>Your conversations and updates</p>
        </div>
    </div>

    <div class="message-search">
        <i class="fas fa-search"></i>
        <input
            type="search"
            id="message-contact-search"
            placeholder="Search individuals or food banks"
            autocomplete="off"
        >
    </div>

    <div class="message-search-results" id="message-search-results" hidden></div>
    <div class="messages-list" id="messages-list" role="list"></div>
    <div class="messages-empty" id="messages-empty">
        <i class="far fa-comment-dots"></i>
        <p>Search for an individual or food bank to start a conversation.</p>
    </div>
</section>

<div class="pa-chat-overlay" id="pa-chat-overlay" aria-hidden="true">
    <section class="chat-panel" role="dialog" aria-modal="true" aria-labelledby="chat-contact-name">
        <header class="chat-header">
            <div class="chat-contact">
                <span id="chat-avatar-slot">
                    <span class="message-avatar message-avatar--green">FB</span>
                </span>
                <div>
                    <h2 id="chat-contact-name">Conversation</h2>
                    <p id="chat-contact-role">Messages</p>
                </div>
            </div>
            <div class="chat-header-actions">
                <button class="chat-profile-btn" type="button" id="chat-profile-btn">
                    <i class="far fa-user-circle"></i>
                    <span>View Profile</span>
                </button>
                <button class="chat-close-btn" type="button" id="chat-close-btn" aria-label="Close chat">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </header>

        <aside class="chat-profile-card" id="chat-profile-card" hidden></aside>
        <div class="chat-body" id="chat-body"></div>

        <form class="chat-composer" id="chat-composer">
            <input type="text" id="chat-input" placeholder="Write a message" aria-label="Write a message" maxlength="2000">
            <button type="submit" aria-label="Send message">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </section>
</div>

<script>
(function () {
    const apiBase = '/foodbank/backend/api/messages';
    const searchInput = document.getElementById('message-contact-search');
    const searchResults = document.getElementById('message-search-results');
    const messagesList = document.getElementById('messages-list');
    const messagesEmpty = document.getElementById('messages-empty');
    const overlay = document.getElementById('pa-chat-overlay');
    const closeBtn = document.getElementById('chat-close-btn');
    const profileBtn = document.getElementById('chat-profile-btn');
    const profileCard = document.getElementById('chat-profile-card');
    const chatAvatarSlot = document.getElementById('chat-avatar-slot');
    const contactName = document.getElementById('chat-contact-name');
    const contactRole = document.getElementById('chat-contact-role');
    const chatBody = document.getElementById('chat-body');
    const composer = document.getElementById('chat-composer');
    const chatInput = document.getElementById('chat-input');
    let activeContact = null;
    let searchTimer = null;

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function avatarMarkup(contact) {
        const initials = escapeHtml(contact.initials || '??');
        const tone = contact.account_type === 'FA' ? 'green' : 'orange';

        if (contact.avatar_url) {
            return `<img class="message-avatar" src="${escapeHtml(contact.avatar_url)}" alt="${escapeHtml(contact.name)}">`;
        }

        return `<span class="message-avatar message-avatar--${tone}">${initials}</span>`;
    }

    function renderConversation(conversation) {
        const contact = conversation.contact;
        const last = conversation.last_message || {};
        const preview = last.is_mine ? `You: ${last.body || ''}` : (last.body || '');

        return `
            <button class="message-preview" type="button" data-contact-id="${contact.account_id}" role="listitem">
                ${avatarMarkup(contact)}
                <span class="message-copy">
                    <strong>${escapeHtml(contact.name)}</strong>
                    <span>${escapeHtml(preview)}</span>
                </span>
                <span class="message-time">${escapeHtml(last.time_label || '')}</span>
            </button>
        `;
    }

    function renderContactResult(contact) {
        return `
            <button class="message-result" type="button" data-contact-id="${contact.account_id}">
                ${avatarMarkup(contact)}
                <span>
                    <strong>${escapeHtml(contact.name)}</strong>
                    <small>${escapeHtml(contact.subtitle)}${contact.email ? ' - ' + escapeHtml(contact.email) : ''}</small>
                </span>
            </button>
        `;
    }

    function renderProfile(contact) {
        const customId = contact.custom_id || 'Not set';
        const email = contact.email || 'Not set';
        const phone = contact.phone || 'Not set';
        const address = contact.address || 'Not set';

        return `
            <div class="profile-card-header">
                ${avatarMarkup(contact)}
                <div>
                    <h3>${escapeHtml(contact.name)}</h3>
                    <p>${escapeHtml(contact.subtitle)}</p>
                </div>
            </div>
            <dl class="profile-details">
                <div>
                    <dt>ID</dt>
                    <dd>${escapeHtml(customId)}</dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd>${escapeHtml(email)}</dd>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>${escapeHtml(phone)}</dd>
                </div>
                <div>
                    <dt>Address</dt>
                    <dd>${escapeHtml(address)}</dd>
                </div>
            </dl>
        `;
    }

    function setEmptyState(show) {
        messagesEmpty.hidden = !show;
    }

    function loadConversations() {
        fetch(`${apiBase}/get_conversations.php`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load conversations');

                messagesList.innerHTML = data.conversations.map(renderConversation).join('');
                setEmptyState(data.conversations.length === 0);
            })
            .catch(error => {
                messagesList.innerHTML = '';
                messagesEmpty.innerHTML = '<i class="fas fa-circle-exclamation"></i><p>Unable to load conversations.</p>';
                setEmptyState(true);
                console.error(error);
            });
    }

    function searchContacts(query) {
        if (query.trim().length < 2) {
            searchResults.hidden = true;
            searchResults.innerHTML = '';
            return;
        }

        fetch(`${apiBase}/search_contacts.php?q=${encodeURIComponent(query.trim())}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to search contacts');

                searchResults.hidden = false;
                searchResults.innerHTML = data.contacts.length
                    ? data.contacts.map(renderContactResult).join('')
                    : '<div class="message-result message-result--empty">No contacts found.</div>';
            })
            .catch(error => {
                searchResults.hidden = false;
                searchResults.innerHTML = '<div class="message-result message-result--empty">Search failed.</div>';
                console.error(error);
            });
    }

    function renderChatMessage(message) {
        const direction = message.is_mine ? 'sent' : 'received';
        return `
            <div class="chat-message chat-message--${direction}">
                <p>${escapeHtml(message.body)}</p>
                <span>${escapeHtml(message.time_label || '')}</span>
            </div>
        `;
    }

    function openChat(contactId) {
        fetch(`${apiBase}/get_messages.php?contact_id=${encodeURIComponent(contactId)}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to load messages');

                activeContact = data.contact;
                chatAvatarSlot.innerHTML = avatarMarkup(activeContact);
                contactName.textContent = activeContact.name;
                contactRole.textContent = activeContact.subtitle;
                profileCard.innerHTML = renderProfile(activeContact);
                profileCard.hidden = true;
                chatBody.innerHTML = data.messages.length
                    ? '<div class="chat-date">Today</div>' + data.messages.map(renderChatMessage).join('')
                    : '<div class="chat-empty">No messages yet.</div>';
                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('pa-chat-open');
                searchResults.hidden = true;
                chatInput.focus();
                chatBody.scrollTop = chatBody.scrollHeight;
                loadConversations();
            })
            .catch(error => {
                console.error(error);
            });
    }

    function closeChat() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pa-chat-open');
        activeContact = null;
        profileCard.hidden = true;
    }

    function sendMessage(body) {
        if (!activeContact || !body.trim()) return;

        const formData = new FormData();
        formData.append('contact_id', activeContact.account_id);
        formData.append('body', body.trim());

        fetch(`${apiBase}/send_message.php`, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Unable to send message');

                const empty = chatBody.querySelector('.chat-empty');
                if (empty) {
                    chatBody.innerHTML = '<div class="chat-date">Today</div>';
                }

                chatBody.insertAdjacentHTML('beforeend', renderChatMessage(data.message));
                chatBody.scrollTop = chatBody.scrollHeight;
                chatInput.value = '';
                loadConversations();
            })
            .catch(error => {
                console.error(error);
            });
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => searchContacts(searchInput.value), 250);
    });

    searchResults.addEventListener('click', event => {
        const result = event.target.closest('.message-result[data-contact-id]');
        if (!result) return;

        searchInput.value = '';
        searchResults.hidden = true;
        openChat(result.dataset.contactId);
    });

    messagesList.addEventListener('click', event => {
        const preview = event.target.closest('.message-preview[data-contact-id]');
        if (!preview) return;

        openChat(preview.dataset.contactId);
    });

    closeBtn.addEventListener('click', closeChat);

    profileBtn.addEventListener('click', () => {
        if (!activeContact) return;
        profileCard.hidden = !profileCard.hidden;
    });

    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeChat();
    });

    composer.addEventListener('submit', event => {
        event.preventDefault();
        sendMessage(chatInput.value);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeChat();
    });

    loadConversations();
})();
</script>
