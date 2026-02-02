// Layout JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Получаем ссылки на кнопки
    const userButton = document.getElementById('user-menu-button');
    const adminButton = document.getElementById('admin-menu-button');
    const notificationsButton = document.getElementById('notifications-menu-button');
    const mobileButton = document.getElementById('mobile-menu-button');

    // Mobile menu toggle
    if (mobileButton) {
        mobileButton.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        });
    }

    // User dropdown toggle
    if (userButton) {
        userButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = document.getElementById('user-dropdown');
            const adminDropdown = document.getElementById('admin-dropdown');
            const notificationsDropdown = document.getElementById('notifications-dropdown');

            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }

            // Close other dropdowns
            if (adminDropdown) {
                adminDropdown.classList.add('hidden');
            }
            if (notificationsDropdown) {
                notificationsDropdown.classList.add('hidden');
            }
        });
    }

    // Notifications dropdown toggle
    if (notificationsButton) {
        notificationsButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = document.getElementById('notifications-dropdown');
            const userDropdown = document.getElementById('user-dropdown');
            const adminDropdown = document.getElementById('admin-dropdown');

            if (dropdown) {
                const isHidden = dropdown.classList.contains('hidden');
                dropdown.classList.toggle('hidden');

                // Load notifications when opening dropdown
                if (isHidden && typeof loadNotifications === 'function') {
                    loadNotifications();
                }
            }

            // Close other dropdowns
            if (userDropdown) {
                userDropdown.classList.add('hidden');
            }
            if (adminDropdown) {
                adminDropdown.classList.add('hidden');
            }
        });
    }

    // Admin dropdown toggle
    if (adminButton) {
        adminButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = document.getElementById('admin-dropdown');
            const userDropdown = document.getElementById('user-dropdown');
            const notificationsDropdown = document.getElementById('notifications-dropdown');

            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }

            // Close other dropdowns
            if (userDropdown) {
                userDropdown.classList.add('hidden');
            }
            if (notificationsDropdown) {
                notificationsDropdown.classList.add('hidden');
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const userDropdown = document.getElementById('user-dropdown');
        const adminDropdown = document.getElementById('admin-dropdown');
        const notificationsDropdown = document.getElementById('notifications-dropdown');

        // Close user dropdown
        if (userButton && userDropdown && !userButton.contains(event.target) && !userDropdown.contains(event.target)) {
            userDropdown.classList.add('hidden');
        }

        // Close admin dropdown
        if (adminButton && adminDropdown && !adminButton.contains(event.target) && !adminDropdown.contains(event.target)) {
            adminDropdown.classList.add('hidden');
        }

        // Close notifications dropdown
        if (notificationsButton && notificationsDropdown && !notificationsButton.contains(event.target) && !notificationsDropdown.contains(event.target)) {
            notificationsDropdown.classList.add('hidden');
        }
    });

    // Mark all read button
    const markAllReadButton = document.getElementById('mark-all-read');
    if (markAllReadButton) {
        markAllReadButton.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }
});

// Notification System
window.showNotification = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('notifications-container');
    const notification = document.createElement('div');

    const typeClasses = {
        'success': 'bg-green-100 border-green-500 text-green-700',
        'error': 'bg-red-100 border-red-500 text-red-700',
        'warning': 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'info': 'bg-blue-100 border-blue-500 text-blue-700'
    };

    notification.className = `border-l-4 p-4 rounded shadow-lg max-w-sm transform translate-x-full transition-transform duration-300 ${typeClasses[type] || typeClasses.info}`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm font-medium">${message}</span>
            </div>
            <button class="ml-4 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;

    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Auto remove
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
};

// Global variables for notification tracking
let lastNotificationCheck = new Date().toISOString();
let notificationPollingInterval = null;
let isPolling = false;

// Update notification badge with real API call
function updateNotificationBadge() {
    if (isPolling) {
        return;
    }

    isPolling = true;

    fetch(window.routes['api.notifications.unread-count'], {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (data.unread_count > 0) {
                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        // Update last check time
        lastNotificationCheck = data.last_updated;
    })
    .catch(error => {
        // В случае ошибки просто скрываем badge
        const badge = document.getElementById('notification-badge');
        if (badge) {
            badge.classList.add('hidden');
        }
    })
    .finally(() => {
        isPolling = false;
    });
}

// Poll for new notifications
function pollForNewNotifications() {
    if (isPolling) return;

    isPolling = true;

    fetch(`${window.routes['api.notifications.poll']}?last_check=${encodeURIComponent(lastNotificationCheck)}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.has_new) {
            // Обновляем badge
            const badge = document.getElementById('notification-badge');
            if (badge && data.unread_count > 0) {
                badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                badge.classList.remove('hidden');
            }

            // Показываем уведомление о новых заявках
            if (data.new_notifications && data.new_notifications.length > 0) {
                data.new_notifications.forEach(notification => {
                    if (notification.type === 'new_ticket') {
                        // Разный тип уведомления в зависимости от приоритета
                        let notificationType = 'info';
                        if (notification.data.ticket_priority === 'high') {
                            notificationType = 'warning';
                        } else if (notification.data.ticket_priority === 'urgent') {
                            notificationType = 'error';
                        }
                        showNotification(`Новая заявка: ${notification.data.ticket_title}`, notificationType);
                    } else if (notification.type === 'ticket_status_changed') {
                        // Тип уведомления в зависимости от нового статуса
                        let notificationType = 'info';
                        if (notification.data.new_status === 'resolved') {
                            notificationType = 'success';
                        } else if (notification.data.new_status === 'closed') {
                            notificationType = 'warning';
                        }
                        showNotification(notification.message, notificationType);
                    } else if (notification.type === 'ticket_assigned') {
                        showNotification(notification.message, 'success');
                    } else {
                        // Для всех остальных типов
                        showNotification(notification.message, 'info');
                    }
                });
            }
        }

        lastNotificationCheck = data.last_updated;
    })
    .catch(error => {
        console.error('Error polling notifications:', error);
    })
    .finally(() => {
        isPolling = false;
    });
}

// Load notifications dropdown content
function loadNotifications() {
    const dropdownContent = document.getElementById('notifications-list');
    if (!dropdownContent) {
        return;
    }

    dropdownContent.innerHTML = '<div class="p-4 text-center text-gray-500">Загрузка...</div>';

    fetch(`${window.routes['api.notifications.index']}?limit=10`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (!data.notifications || data.notifications.length === 0) {
            dropdownContent.innerHTML = '<div class="p-4 text-center text-gray-500">Нет уведомлений</div>';
            return;
        }

        let html = '';
        data.notifications.forEach(function(notification) {
            const isRead = notification.read_at !== null;
            const createdAt = new Date(notification.created_at).toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });

            html += '<div class="relative block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 ' + (isRead ? '' : 'bg-blue-50') + '" data-notification-id="' + notification.id + '">';
            html += '<div class="flex items-start justify-between">';
            html += '<div class="flex-1">';
            html += '<p class="text-sm font-medium text-gray-900">' + notification.title + '</p>';
            html += '<p class="text-sm text-gray-600 mt-1">' + notification.message + '</p>';
            html += '<p class="text-xs text-gray-500 mt-2">' + createdAt + '</p>';
            html += '</div>';

            if (!isRead) {
                html += '<div class="w-2 h-2 bg-blue-500 rounded-full ml-2 mt-1"></div>';
            }

            html += '</div>';

            if (notification.url) {
                html += '<a href="' + notification.url + '" class="block absolute inset-0" data-id="' + notification.id + '"></a>';
            }

            html += '</div>'; // Close the notification item div
        });

        dropdownContent.innerHTML = html;

        // Add click handlers for notification links
        const links = dropdownContent.querySelectorAll('[data-id]');
        links.forEach(function(link) {
            link.addEventListener('click', function(event) {
                const notificationId = this.getAttribute('data-id');
                markNotificationAsRead(notificationId);
            });
        });
    })
    .catch(function(error) {
        dropdownContent.innerHTML = '<div class="p-4 text-center text-red-500">Ошибка загрузки</div>';
    });
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
    fetch(window.routes['api.notifications.mark-as-read'].replace('__ID__', notificationId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the badge count
            updateNotificationBadge();
        }
    })
    .catch(error => {
        // Ошибка при отметке как прочитанное
    });
}

// Mark all notifications as read
function markAllNotificationsAsRead() {
    fetch(window.routes['api.notifications.mark-all-as-read'], {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.classList.add('hidden');
            }
            loadNotifications(); // Refresh the dropdown
            showNotification('Все уведомления отмечены как прочитанные', 'success');
        }
    })
    .catch(error => {
        // Ошибка при отметке всех как прочитанных
    });
}

// Start polling when page loads
function startNotificationPolling() {
    // Set up polling every 30 seconds for better performance
    notificationPollingInterval = setInterval(() => {
        updateNotificationBadge();
    }, 30000);

    // Also check when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            setTimeout(updateNotificationBadge, 1000);
        }
    });
}

// Stop polling when page is about to unload
function stopNotificationPolling() {
    if (notificationPollingInterval) {
        clearInterval(notificationPollingInterval);
        notificationPollingInterval = null;
    }
}

// Initialize notification system
// Check if notification elements exist
const notificationsButton = document.getElementById('notifications-menu-button');
const notificationBadge = document.getElementById('notification-badge');
const notificationsDropdown = document.getElementById('notifications-dropdown');

// Initial check after page load
setTimeout(() => {
    updateNotificationBadge();
}, 1000);

// Start polling
startNotificationPolling();
window.addEventListener('beforeunload', stopNotificationPolling);
