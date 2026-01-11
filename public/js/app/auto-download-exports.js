document.addEventListener('livewire:init', () => {
    Livewire.on('database-notifications-loaded', () => {
        setTimeout(() => {
            autoDownloadExports();
        }, 100);
    });
});

window.addEventListener('load', () => {
    setTimeout(() => {
        autoDownloadExports();
    }, 1000);
});

function autoDownloadExports() {
    const notificationElements = document.querySelectorAll('[wire\\:key^="database-notification-"]');
    
    notificationElements.forEach((notificationElement) => {
        const downloadLink = notificationElement.querySelector('a[href*="/filament/exports/"][href*="/download"]');
        
        if (downloadLink && !notificationElement.dataset.autoDownloaded) {
            notificationElement.dataset.autoDownloaded = 'true';
            
            const downloadUrl = downloadLink.href;
            
            fetch(downloadUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Download failed');
                    }
                    return response.blob();
                })
                .then(blob => {
                    const contentDisposition = downloadLink.getAttribute('download');
                    let filename = 'export.csv';
                    
                    if (contentDisposition) {
                        filename = contentDisposition;
                    } else {
                        const urlParts = downloadUrl.split('/');
                        const exportId = urlParts[urlParts.indexOf('exports') + 1];
                        filename = `registrations-export-${exportId}.csv`;
                    }
                    
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = filename;
                    
                    document.body.appendChild(a);
                    a.click();
                    
                    setTimeout(() => {
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }, 100);
                })
                .catch(error => {
                    console.error('Auto-download failed:', error);
                });
        }
    });
}
