self.addEventListener('install', function(event) {
    // console.log('Service worker installed');
});

self.addEventListener('activate', function(event) {
    // console.log('Service worker activated');
});

self.addEventListener('push', function(event) {
    // Parse the JSON data from the push message
    var data = event.data.json();

    // Use the data to set the title, body, and other properties of the notification
    var title = data.title || 'ODARENA';
    var body = data.body || 'Something happened in your dominion!';
    var icon = data.icon || '/assets/app/images/odarena-icon.png';
    var options = {
        body: body,
        icon: icon,
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: '2'
        },
        actions: [
            {action: 'status', title: 'Check Status', icon: 'images/checkmark.png'}
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close(); // Close the notification

    // Get the URL from the notification data
    var url = event.notification.data.url;

    // Open the URL in a new window or tab
    event.waitUntil(
        clients.openWindow(url)
    );
});