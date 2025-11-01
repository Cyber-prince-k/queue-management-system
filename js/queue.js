// Queue Management JavaScript for QECH Queue System
// Define API_BASE_URL only if not already defined
if (typeof API_BASE_URL === 'undefined') {
    var API_BASE_URL = 'http://localhost/queue%20system/php/api';
}

// Promote due appointments into queue tokens (backend-driven)
async function promoteDueAppointments(departmentCode = '') {
    try {
        const url = departmentCode
            ? `${API_BASE_URL}/appointments.php?action=promote-due&department=${encodeURIComponent(departmentCode)}`
            : `${API_BASE_URL}/appointments.php?action=promote-due`;
        const res = await fetch(url, { method: 'GET', credentials: 'include' });
        if (!res.ok) {
            return { success: false };
        }
        const data = await res.json();
        return data;
    } catch (e) {
        return { success: false };
    }
}

// Safe toast wrapper - only calls showToast if it exists
function safeToast(message, type = 'info') {
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
}

// Create new queue token (Patient Registration)
async function createQueueToken(patientData) {
    try {
        // Validate required fields
        if (!patientData.patient_name || !patientData.department) {
            if (typeof showToast === 'function') {
                safeToast('Patient name and department are required', 'error');
            }
            return { success: false, message: 'Missing required fields' };
        }

        if (typeof showToast === 'function') {
            safeToast('Creating queue token...', 'info');
        }

        const response = await fetch(`${API_BASE_URL}/queue.php?action=create`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(patientData)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            if (typeof showToast === 'function') {
                safeToast(`✓ Token created: ${data.token.token_number}`, 'success');
            }
            
            // Display token details
            displayTokenDetails(data.token);
        } else {
            if (typeof showToast === 'function') {
                safeToast(data.message || 'Failed to create token', 'error');
            }
        }

        return data;
    } catch (error) {
        console.error('Create token error:', error);
        
        if (typeof showToast === 'function') {
            if (error.message.includes('Failed to fetch')) {
                safeToast('Cannot connect to server. Please ensure XAMPP is running.', 'error');
            } else if (error.message.includes('HTTP error')) {
                safeToast('Server error occurred. Please check the console for details.', 'error');
            } else {
                safeToast('Failed to create token. Please try again.', 'error');
            }
        }
        
        return { success: false, message: error.message };
    }
}

// Get queue status for a department
async function getQueueStatus(departmentCode = '') {
    try {
        const url = departmentCode 
            ? `${API_BASE_URL}/queue.php?action=status&department=${departmentCode}`
            : `${API_BASE_URL}/queue.php?action=status`;

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            return data.tokens;
        } else {
            if (typeof showToast === 'function') {
                safeToast(data.message || 'Failed to get queue status', 'error');
            }
            return [];
        }
    } catch (error) {
        console.error('Get queue status error:', error);
        
        if (error.message.includes('Failed to fetch') && typeof showToast === 'function') {
            safeToast('Cannot connect to server', 'error');
        }
        
        return [];
    }
}

// Call next patient (Staff function)
async function callNextPatient(departmentCode) {
    try {
        if (!departmentCode) {
            safeToast('Department is required', 'error');
            return { success: false };
        }

        safeToast('Calling next patient...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=call_next&department=${departmentCode}`, {
            method: 'POST',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast(`✓ Now serving: ${data.token.token_number}`, 'success');
            
            // Play notification sound (optional)
            playNotificationSound();
            
            // Refresh queue display
            await refreshQueueDisplay(departmentCode);
        } else {
            safeToast(data.message || 'No patients in queue', 'error');
        }

        return data;
    } catch (error) {
        console.error('Call next patient error:', error);
        safeToast('Failed to call next patient', 'error');
        return { success: false, message: error.message };
    }
}

// Complete token (Staff function)
async function completeToken(tokenId) {
    try {
        if (!tokenId) {
            safeToast('Token ID is required', 'error');
            return { success: false };
        }

        safeToast('Completing token...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=complete&token_id=${tokenId}`, {
            method: 'POST',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast('✓ Token completed successfully', 'success');
        } else {
            safeToast(data.message || 'Failed to complete token', 'error');
        }

        return data;
    } catch (error) {
        console.error('Complete token error:', error);
        safeToast('Failed to complete token', 'error');
        return { success: false, message: error.message };
    }
}

// Display token details in a modal or section
function displayTokenDetails(token) {
    const detailsHtml = `
        <div class="token-details" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 16px; margin: 1.5rem 0; box-shadow: 0 10px 30px rgba(0,0,0,0.3); text-align: center; color: white; animation: slideIn 0.5s ease-out;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🎫</div>
            <h3 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: bold;">Token Created Successfully!</h3>
            
            <div style="background: rgba(255,255,255,0.95); padding: 1.5rem; border-radius: 12px; margin: 1rem 0; color: #1e293b;">
                <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">Your Token Number</p>
                <p style="font-size: 2.5rem; font-weight: bold; margin: 0; color: #2563eb; font-family: monospace; letter-spacing: 2px;">
                    ${token.token_number}
                </p>
            </div>
            
            <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <p style="margin: 0; font-size: 1.1rem;">
                    <strong>Queue Position:</strong> <span style="font-size: 1.3rem; font-weight: bold;">#${token.queue_position}</span>
                </p>
            </div>
            
            <div style="background: rgba(255,255,255,0.15); padding: 1rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid #fbbf24;">
                <p style="margin: 0; font-size: 0.95rem; line-height: 1.6;">
                    ⚠️ <strong>Important:</strong> Please save this token number. You will need it to check your queue status.
                </p>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button onclick="window.print()" class="btn" style="background: white; color: #667eea; padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem;">
                    🖨️ Print Token
                </button>
                <button onclick="copyTokenToClipboard('${token.token_number}')" class="btn" style="background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; border: 2px solid white; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem;">
                    📋 Copy Number
                </button>
            </div>
        </div>
        
        <style>
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @media print {
                body * {
                    visibility: hidden;
                }
                .token-details, .token-details * {
                    visibility: visible;
                }
                .token-details {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                .token-details button {
                    display: none !important;
                }
            }
        </style>
    `;
    
    // Find a container to display the token
    const container = document.getElementById('token-display') || document.getElementById('queue-status-result');
    if (container) {
        container.innerHTML = detailsHtml;
        container.classList.remove('hidden');
        
        // Scroll to the token display smoothly
        setTimeout(() => {
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    } else {
        console.error('Token display container not found!');
    }
}

// Copy token to clipboard
function copyTokenToClipboard(tokenNumber) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(tokenNumber).then(() => {
            safeToast('✓ Token number copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Failed to copy:', err);
            fallbackCopyToClipboard(tokenNumber);
        });
    } else {
        fallbackCopyToClipboard(tokenNumber);
    }
}

// Fallback copy method for older browsers
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        safeToast('✓ Token number copied!', 'success');
    } catch (err) {
        safeToast('Could not copy token number', 'error');
    }
    document.body.removeChild(textArea);
}

// Refresh queue display
async function refreshQueueDisplay(departmentCode, showActions = false) {
    // First, promote any due appointments for this department (idempotent on backend)
    await promoteDueAppointments(departmentCode);
    const tokens = await getQueueStatus(departmentCode);
    
    const queueList = document.getElementById('queue-list');
    if (!queueList) return;
    
    if (tokens.length === 0) {
        queueList.innerHTML = '<p style="text-align: center; color: #64748b;">No patients in queue</p>';
        return;
    }
    
    let html = '';
    tokens.forEach((token, index) => {
        const priorityClass = token.priority_type !== 'no' ? 'priority-item' : '';
        const statusBadge = token.status === 'serving' ? '<span style="background: #10b981; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">SERVING</span>' : '';
        
        // Patient details section
        let patientDetails = '';
        if (showActions) {
            patientDetails = `
                <div style="margin-top: 0.5rem; padding: 0.5rem; background: #f8fafc; border-radius: 6px; font-size: 0.9rem;">
                    ${token.patient_age ? `<div style="display: inline-block; margin-right: 1rem;"><strong>Age:</strong> ${token.patient_age}</div>` : ''}
                    ${token.patient_phone ? `<div style="display: inline-block; margin-right: 1rem;"><strong>Phone:</strong> ${token.patient_phone}</div>` : ''}
                    ${token.service_type ? `<div style="margin-top: 0.25rem;"><strong>Service:</strong> ${token.service_type}</div>` : ''}
                </div>
            `;
        }
        
        // Action buttons for staff
        let actionButtons = '';
        if (showActions) {
            actionButtons = `
                <div class="queue-actions" style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="btn btn-sm btn-success" onclick="handleMarkAttended(${token.id}, '${departmentCode}')" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        ✓ Attended
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="handleReassign(${token.id}, '${departmentCode}')" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        ↔ Reassign
                    </button>
                </div>
            `;
        }
        
        html += `
            <div class="queue-item ${priorityClass}" data-token-id="${token.id}" style="padding: 1rem; border-bottom: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong style="font-size: 1.1rem;">${token.token_number}</strong> - ${token.patient_name}
                        ${statusBadge}
                        ${token.priority_type !== 'no' ? '<br><small style="color: #ef4444; font-weight: 600;">Priority: ' + token.priority_type + '</small>' : ''}
                    </div>
                    <div style="text-align: right;">
                        <small style="color: #64748b;">Position: ${index + 1}</small>
                    </div>
                </div>
                ${patientDetails}
                ${actionButtons}
            </div>
        `;
    });
    
    queueList.innerHTML = html;
    
    // Update queue statistics if elements exist
    updateQueueStatistics(tokens);
}

// Update queue statistics display
function updateQueueStatistics(tokens) {
    const totalWaitingEl = document.getElementById('total-waiting');
    const currentlyServingEl = document.getElementById('currently-serving');
    const priorityPatientsEl = document.getElementById('priority-patients');
    const queueCountBadge = document.getElementById('queue-count-badge');
    
    if (totalWaitingEl) {
        const waiting = tokens.filter(t => t.status === 'waiting').length;
        totalWaitingEl.textContent = waiting;
    }
    
    if (currentlyServingEl) {
        const serving = tokens.filter(t => t.status === 'serving').length;
        currentlyServingEl.textContent = serving;
    }
    
    if (priorityPatientsEl) {
        const priority = tokens.filter(t => t.priority_type !== 'no').length;
        priorityPatientsEl.textContent = priority;
    }
    
    if (queueCountBadge) {
        queueCountBadge.textContent = tokens.length;
    }
}

// Auto-refresh queue display every 10 seconds
function startAutoRefresh(departmentCode, interval = 10000, showActions = false) {
    // Initial load
    refreshQueueDisplay(departmentCode, showActions);
    
    // Set up interval
    return setInterval(() => {
        refreshQueueDisplay(departmentCode, showActions);
    }, interval);
}

// Stop auto-refresh
function stopAutoRefresh(intervalId) {
    if (intervalId) {
        clearInterval(intervalId);
    }
}

// Play notification sound (optional)
function playNotificationSound() {
    try {
        // Create a simple beep sound using Web Audio API
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (error) {
        console.log('Audio notification not available');
    }
}

// Update public display
async function updatePublicDisplay() {
    const departments = ['opd', 'maternity', 'emergency', 'pediatrics'];
    
    for (const dept of departments) {
        const tokens = await getQueueStatus(dept);
        
        const deptElement = document.getElementById(`${dept}-queue`);
        if (!deptElement) continue;
        
        const serving = tokens.find(t => t.status === 'serving');
        const waiting = tokens.filter(t => t.status === 'waiting');
        
        const currentToken = deptElement.querySelector('.current-token');
        const nextToken = deptElement.querySelector('.next-token');
        
        if (currentToken) {
            currentToken.textContent = serving ? serving.token_number : '-';
        }
        
        if (nextToken) {
            nextToken.textContent = waiting.length > 0 ? waiting[0].token_number : '-';
        }
    }
}

// Format department name
function formatDepartmentName(code) {
    const names = {
        'opd': 'Outpatient Department (OPD)',
        'maternity': 'Maternity',
        'emergency': 'Emergency',
        'pediatrics': 'Pediatrics'
    };
    return names[code] || code;
}

// Pause queue for a department
async function pauseQueue(departmentCode) {
    try {
        if (!departmentCode) {
            safeToast('Department is required', 'error');
            return { success: false };
        }

        safeToast('Pausing queue...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=pause_queue&department=${departmentCode}`, {
            method: 'POST',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast('✓ Queue paused successfully', 'success');
        } else {
            safeToast(data.message || 'Failed to pause queue', 'error');
        }

        return data;
    } catch (error) {
        console.error('Pause queue error:', error);
        safeToast('Failed to pause queue', 'error');
        return { success: false, message: error.message };
    }
}

// Resume queue for a department
async function resumeQueue(departmentCode) {
    try {
        if (!departmentCode) {
            safeToast('Department is required', 'error');
            return { success: false };
        }

        safeToast('Resuming queue...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=resume_queue&department=${departmentCode}`, {
            method: 'POST',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast('✓ Queue resumed successfully', 'success');
        } else {
            safeToast(data.message || 'Failed to resume queue', 'error');
        }

        return data;
    } catch (error) {
        console.error('Resume queue error:', error);
        safeToast('Failed to resume queue', 'error');
        return { success: false, message: error.message };
    }
}

// Reassign patient to another department
async function reassignPatient(tokenId, newDepartmentCode) {
    try {
        if (!tokenId || !newDepartmentCode) {
            safeToast('Token ID and new department are required', 'error');
            return { success: false };
        }

        safeToast('Reassigning patient...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=reassign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({
                token_id: tokenId,
                new_department: newDepartmentCode
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast('✓ Patient reassigned successfully', 'success');
        } else {
            safeToast(data.message || 'Failed to reassign patient', 'error');
        }

        return data;
    } catch (error) {
        console.error('Reassign patient error:', error);
        safeToast('Failed to reassign patient', 'error');
        return { success: false, message: error.message };
    }
}

// Mark patient as attended
async function markPatientAttended(tokenId) {
    try {
        if (!tokenId) {
            safeToast('Token ID is required', 'error');
            return { success: false };
        }

        safeToast('Marking patient as attended...', 'info');

        const response = await fetch(`${API_BASE_URL}/queue.php?action=mark_attended&token_id=${tokenId}`, {
            method: 'POST',
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            safeToast('✓ Patient marked as attended', 'success');
        } else {
            safeToast(data.message || 'Failed to mark patient as attended', 'error');
        }

        return data;
    } catch (error) {
        console.error('Mark attended error:', error);
        safeToast('Failed to mark patient as attended', 'error');
        return { success: false, message: error.message };
    }
}
