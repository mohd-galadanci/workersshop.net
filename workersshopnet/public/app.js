class FoodRequestApp {
    constructor() {
        this.apiUrl = '/api';
        this.token = localStorage.getItem('token');
        this.user = null;
        this.init();
    }

    init() {
        this.addCustomStyles();
        this.createFloatingElements();
        this.bindEvents();

        if (this.token) {
            this.validateToken();
        }
    }

    addCustomStyles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            .floating-btn {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: var(--primary-color);
                color: white;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                transition: all 0.3s ease;
            }

            .floating-btn:hover {
                transform: scale(1.1);
                box-shadow: var(--shadow-xl);
            }
        `;
        document.head.appendChild(style);
    }

    createFloatingElements() {
        // Create floating action button for new requests
        const floatingBtn = document.createElement('button');
        floatingBtn.className = 'floating-btn';
        floatingBtn.innerHTML = '<i class="fas fa-plus"></i>';
        floatingBtn.addEventListener('click', () => this.showNewRequestModal());
        document.body.appendChild(floatingBtn);
    }

    bindEvents() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // New request form
        const newRequestForm = document.getElementById('newRequestForm');
        if (newRequestForm) {
            newRequestForm.addEventListener('submit', (e) => this.handleNewRequest(e));
        }

        // Modal close buttons
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });

        // Auth toggle buttons
        const showRegisterBtn = document.getElementById('showRegister');
        const showLoginBtn = document.getElementById('showLogin');

        if (showRegisterBtn) {
            showRegisterBtn.addEventListener('click', () => this.toggleAuthMode());
        }

        if (showLoginBtn) {
            showLoginBtn.addEventListener('click', () => this.toggleAuthMode());
        }

        // Logout button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }
    }

    async validateToken() {
        try {
            const response = await fetch(`${this.apiUrl}/user`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.user = await response.json();
                this.showDashboard();
                this.loadRequests();
                this.loadStats();
            } else {
                localStorage.removeItem('token');
                this.token = null;
                this.showAuthSection();
            }
        } catch (error) {
            console.error('Token validation failed:', error);
            localStorage.removeItem('token');
            this.token = null;
            this.showAuthSection();
        }
    }

    async handleLogin(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            ippis_no: formData.get('ippis_no'),
            password: formData.get('password')
        };

        try {
            const response = await fetch(`${this.apiUrl}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                this.token = result.token;
                this.user = result.user;
                localStorage.setItem('token', this.token);
                this.showDashboard();
                this.loadRequests();
                this.loadStats();
                this.showNotification('Login successful!', 'success');
            } else {
                this.showNotification(result.error || 'Login failed', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showNotification('Login failed. Please try again.', 'error');
        }
    }

    async handleRegister(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            ippis_no: formData.get('ippis_no'),
            department: formData.get('department'),
            password: formData.get('password'),
            password_confirmation: formData.get('password_confirmation')
        };

        try {
            const response = await fetch(`${this.apiUrl}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                this.token = result.token;
                this.user = result.user;
                localStorage.setItem('token', this.token);
                this.showDashboard();
                this.loadRequests();
                this.loadStats();
                this.showNotification('Registration successful!', 'success');
            } else {
                const errorMessage = result.messages ? 
                    Object.values(result.messages).flat().join(', ') : 
                    result.error || 'Registration failed';
                this.showNotification(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showNotification('Registration failed. Please try again.', 'error');
        }
    }

    async handleNewRequest(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            item: formData.get('item'),
            quantity: parseInt(formData.get('quantity')),
            requested_date: formData.get('requested_date'),
            notes: formData.get('notes')
        };

        try {
            const response = await fetch(`${this.apiUrl}/requests`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                this.closeModal(document.getElementById('newRequestModal'));
                this.loadRequests();
                this.loadStats();
                this.showNotification('Request submitted successfully!', 'success');
                e.target.reset();
            } else {
                const errorMessage = result.messages ? 
                    Object.values(result.messages).flat().join(', ') : 
                    result.error || 'Request submission failed';
                this.showNotification(errorMessage, 'error');
            }
        } catch (error) {
            console.error('Request submission error:', error);
            this.showNotification('Request submission failed. Please try again.', 'error');
        }
    }

    async loadRequests() {
        try {
            const response = await fetch(`${this.apiUrl}/requests`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.displayRequests(result.data);
            }
        } catch (error) {
            console.error('Failed to load requests:', error);
        }
    }

    async loadStats() {
        try {
            const response = await fetch(`${this.apiUrl}/requests`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                this.updateStats(result.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    updateStats(requests) {
        const stats = {
            pending: requests.filter(r => r.status === 'pending').length,
            approved: requests.filter(r => r.status === 'approved').length,
            rejected: requests.filter(r => r.status === 'rejected').length
        };

        document.getElementById('pendingCount').textContent = stats.pending;
        document.getElementById('approvedCount').textContent = stats.approved;
        document.getElementById('rejectedCount').textContent = stats.rejected;
    }

    displayRequests(requests) {
        const requestsList = document.getElementById('requestsList');
        if (!requestsList) return;

        if (requests.length === 0) {
            requestsList.innerHTML = '<div class="no-requests">No requests found</div>';
            return;
        }

        requestsList.innerHTML = requests.map(request => `
            <div class="request-card">
                <div class="request-header">
                    <h3>${request.item}</h3>
                    <span class="status-badge status-${request.status}">${request.status}</span>
                </div>
                <div class="request-details">
                    <p><i class="fas fa-calendar"></i> ${new Date(request.requested_date).toLocaleDateString()}</p>
                    <p><i class="fas fa-sort-numeric-up"></i> Quantity: ${request.quantity}</p>
                    ${request.notes ? `<p><i class="fas fa-sticky-note"></i> ${request.notes}</p>` : ''}
                </div>
                <div class="request-footer">
                    <small>Requested: ${new Date(request.created_at).toLocaleString()}</small>
                    ${request.status === 'pending' ? `
                        <button class="btn btn-danger btn-sm" onclick="app.deleteRequest(${request.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async deleteRequest(id) {
        if (!confirm('Are you sure you want to delete this request?')) return;

        try {
            const response = await fetch(`${this.apiUrl}/requests/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.loadRequests();
                this.loadStats();
                this.showNotification('Request deleted successfully!', 'success');
            } else {
                this.showNotification('Failed to delete request', 'error');
            }
        } catch (error) {
            console.error('Delete request error:', error);
            this.showNotification('Failed to delete request', 'error');
        }
    }

    showAuthSection() {
        document.getElementById('authSection').style.display = 'flex';
        document.getElementById('dashboardSection').style.display = 'none';
        document.getElementById('welcomeSection').style.display = 'block';
    }

    showDashboard() {
        document.getElementById('authSection').style.display = 'none';
        document.getElementById('dashboardSection').style.display = 'block';
        document.getElementById('welcomeSection').style.display = 'none';

        const userNameSpan = document.getElementById('userName');
        if (userNameSpan && this.user) {
            userNameSpan.textContent = this.user.name;
        }
    }

    showNewRequestModal() {
        if (!this.user) {
            this.showNotification('Please login first', 'error');
            return;
        }
        document.getElementById('newRequestModal').style.display = 'flex';
    }

    toggleAuthMode() {
        const loginCard = document.getElementById('loginCard');
        const registerCard = document.getElementById('registerCard');

        if (loginCard.style.display === 'none') {
            loginCard.style.display = 'block';
            registerCard.style.display = 'none';
        } else {
            loginCard.style.display = 'none';
            registerCard.style.display = 'block';
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async logout() {
        try {
            await fetch(`${this.apiUrl}/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            localStorage.removeItem('token');
            this.token = null;
            this.user = null;
            this.showAuthSection();
            this.showNotification('Logged out successfully', 'success');
        }
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 2rem;
                right: 2rem;
                background: ${type === 'error' ? 'var(--danger-color)' : 'var(--secondary-color)'};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg);
                z-index: 2000;
                animation: slideIn 0.3s ease-out;
            ">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                ${message}
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
    window.app = new FoodRequestApp();
});

// Initialize the app
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    initializeChatbot();
});

// Chatbot functionality
function initializeChatbot() {
    const chatbotWidget = document.getElementById('chatbot-widget');
    const chatbotToggle = document.getElementById('chatbot-toggle');
    const chatbotContainer = document.getElementById('chatbot-container');
    const chatbotClose = document.getElementById('chatbot-close');
    const chatbotInput = document.getElementById('chatbot-input');
    const chatbotSend = document.getElementById('chatbot-send');
    const chatbotMessages = document.getElementById('chatbot-messages');

    // Show chatbot widget when user is logged in
    if (getToken()) {
        chatbotWidget.style.display = 'block';
        addBotMessage("Welcome to workersshop! I am Xiera, here to help you find the kind of food items you want. We have almost everything you'll be needing.");
        addBotMessage("First, I'd like to know from which ministry, agency, or commission you're coming, as I need to confirm if it's registered on our platform.");
        addBotMessage("Please be patient as this background check will only take about 4 minutes. Thank you for understanding.");
    }

    chatbotToggle.addEventListener('click', function() {
        chatbotContainer.style.display = chatbotContainer.style.display === 'none' ? 'block' : 'none';
    });

    chatbotClose.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
    });

    chatbotSend.addEventListener('click', sendMessage);
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;

        addUserMessage(message);
        chatbotInput.value = '';

        // Determine if the user is asking about prices, tracking, or seasonal messages
        if (message.includes('price')) {
            // Logic to fetch and return item prices
            addBotMessage("Please provide the item name. I'll fetch the price for you.");
        } else if (message.includes('track order')) {
            // Logic to track the user's orders
            addBotMessage("I'll track your order. Please provide your order ID.");
        } else if (message.includes('seasonal message')) {
            // Logic to send seasonal messages
            addBotMessage("🎉 Here are some seasonal greetings for you!");
        }
        // More logic for background check and integration of additional features

        fetch('/api/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                addBotMessage(data.message);
            } else {
                addBotMessage('Sorry, I encountered an error. Please try again.');
            }
        })
        .catch(error => {
            console.error('Chatbot error:', error);
            addBotMessage('Sorry, I\'m having trouble connecting. Please try again later.');
        });
    }

    function addUserMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message user';
        messageDiv.textContent = message;
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function addBotMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chat-message bot';
        messageDiv.textContent = message;
        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
}