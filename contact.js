function createContactCard(contact) {
    const card = document.createElement('article');
    card.className = 'contact-card';

    const name = document.createElement('h3');
    name.textContent = contact.name || 'Unknown guide';
    card.appendChild(name);

    const desc = document.createElement('p');
    desc.textContent = contact.description || 'Available guide contact information.';
    card.appendChild(desc);

    const meta = document.createElement('div');
    meta.className = 'contact-meta';

    if (contact.email) {
        const emailEl = document.createElement('span');
        emailEl.textContent = 'Email: ' + contact.email;
        meta.appendChild(emailEl);
    }

    if (contact.phone_number) {
        const phoneEl = document.createElement('span');
        phoneEl.textContent = 'Phone: ' + contact.phone_number;
        meta.appendChild(phoneEl);
    }

    if (!contact.email && !contact.phone_number) {
        const noteEl = document.createElement('span');
        noteEl.textContent = 'Contact details not available.';
        meta.appendChild(noteEl);
    }

    card.appendChild(meta);
    return card;
}

function loadContactDirectory() {
    fetch('get_contacts.php')
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('contactsGrid');
            grid.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                grid.innerHTML = '<div class="contact-card"><h3>No contacts found</h3><p>There are no GuideMate contacts available right now.</p></div>';
                return;
            }

            data.forEach(contact => {
                grid.appendChild(createContactCard(contact));
            });
        })
        .catch(() => {
            const grid = document.getElementById('contactsGrid');
            grid.innerHTML = '<div class="contact-card"><h3>Unable to load contacts</h3><p>Please refresh the page or try again later.</p></div>';
        });
}

function initContactPage() {
    const logoutBtn = document.getElementById('contactLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            if (typeof handleLogout === 'function') {
                handleLogout();
            } else {
                window.location.href = 'logout.php';
            }
        });
    }

    loadContactDirectory();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContactPage);
} else {
    initContactPage();
}
