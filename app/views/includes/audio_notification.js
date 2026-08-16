// Audio notification system for waiter calls
let audioContext = null;
let audioUnlocked = false;
let ringingInterval = null;

function startContinuousRinging() {
    if (!audioContext) {
        audioContext = new AudioContext();
    }
    
    if (audioContext.state === 'suspended') {
        audioContext.resume();
    }
    
    // Play immediately
    playAlarmSound();
    
    // Continue ringing every 2 seconds
    ringingInterval = setInterval(() => {
        playAlarmSound();
    }, 2000);
}

function stopContinuousRinging() {
    if (ringingInterval) {
        clearInterval(ringingInterval);
        ringingInterval = null;
    }
    
    if (audioContext) {
        audioContext.close();
        audioContext = null;
    }
}

function playAlarmSound() {
    if (!audioContext || audioContext.state === 'closed') {
        audioContext = new AudioContext();
    }
    
    if (audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            playAlarmSoundNow();
        });
    } else {
        playAlarmSoundNow();
    }
}

function playAlarmSoundNow() {
    if (!audioContext || audioContext.state === 'closed') return;
    
    const now = audioContext.currentTime;
    
    // Create 3 layered oscillators for louder sound
    for (let layer = 0; layer < 3; layer++) {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.type = 'square';
        gainNode.gain.value = 0.3; // 0.9 total gain when combined
        
        // Siren pattern with 6 steps
        const frequencies = [800, 1000, 1200, 1450, 1200, 1000];
        const stepDuration = 0.1;
        
        frequencies.forEach((freq, index) => {
            oscillator.frequency.setValueAtTime(
                freq + (layer * 50),
                now + (index * stepDuration)
            );
        });
        
        oscillator.start(now);
        oscillator.stop(now + (stepDuration * frequencies.length));
    }
}

function checkForNewCalls() {
    // Skip if BASE_URL or RESTAURANT_ID not defined
    if (typeof BASE_URL === 'undefined' || typeof RESTAURANT_ID === 'undefined') {
        return;
    }
    
    fetch(`${BASE_URL}/staff/api/get_waiter_calls_count`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ restaurant_id: RESTAURANT_ID })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const currentCount = data.count || 0;
            const previousCount = parseInt(localStorage.getItem('previous_call_count') || '0');
            
            // Stop ringing if no calls
            if (currentCount === 0) {
                stopContinuousRinging();
                localStorage.setItem('previous_call_count', '0');
                return;
            }
            
            // Start ringing only for NEW calls
            if (currentCount > previousCount && currentCount > 0) {
                // New call received - start ringing
                startContinuousRinging();
                
                // Desktop notification
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('NEW WAITER CALL!', {
                        body: `You have ${currentCount} pending call(s)`,
                        icon: BASE_URL + '/assets/images/logo.png',
                        requireInteraction: true
                    });
                }
                
                // Reload page after 3 seconds to show new call
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
            
            localStorage.setItem('previous_call_count', currentCount);
        }
    })
    .catch(err => {
        
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('audioEnableBanner');
    
    // Don't initialize if banner doesn't exist (fragment mode, etc)
    if (!banner) {
        
        return;
    }
    
    // Create AudioContext only after checking banner exists
    try {
        audioContext = new AudioContext();
        
        // Check if audio is already unlocked
        if (audioContext.state === 'running') {
            audioUnlocked = true;
            banner.style.display = 'none';
        } else {
            banner.style.display = 'block';
        }
    } catch (e) {
        
        banner.style.display = 'none';
        return;
    }
    
    // Function to unlock audio
    const unlockAudio = () => {
        if (!audioUnlocked && audioContext) {
            audioContext.resume().then(() => {
                audioUnlocked = true;
                banner.style.display = 'none';
                
                // Play silent sound to fully unlock
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                gainNode.gain.value = 0.001;
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.01);
            });
        }
    };
    
    // Multiple event listeners for maximum compatibility
    ['click', 'touchstart', 'keydown', 'mousedown', 'touchend', 'mousemove'].forEach(event => {
        document.body.addEventListener(event, unlockAudio, { once: true });
    });
    
    // Make banner clickable
    if (banner) {
        banner.addEventListener('click', unlockAudio);
    }
    
    // Auto-attempt to unlock after 100ms
    setTimeout(() => {
        if (!audioUnlocked) {
            audioContext.resume().catch(() => {});
            
            // Try programmatic click
            const clickEvent = new MouseEvent('click', {
                view: window,
                bubbles: true,
                cancelable: true
            });
            document.body.dispatchEvent(clickEvent);
        }
    }, 100);
    
    // Check for calls every 5 seconds
    setInterval(checkForNewCalls, 5000);
    
    // Initial check
    checkForNewCalls();
    
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});

