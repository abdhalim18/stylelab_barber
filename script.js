// Initialize booking form
const bookingForm = document.getElementById('bookingForm');
const timeSelect = document.getElementById('time');

// Available time slots
const availableTimes = [
    '09:00', '10:00', '11:00', '12:00',
    '14:00', '15:00', '16:00', '17:00'
];

// Populate time slots
availableTimes.forEach(time => {
    const option = document.createElement('option');
    option.value = time;
    option.textContent = time;
    timeSelect.appendChild(option);
});

// Form submission handler
bookingForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        service: document.getElementById('service').value,
        date: document.getElementById('date').value,
        time: document.getElementById('time').value
    };

    try {
        // Validate form data
        if (!validateForm(formData)) {
            alert('Harap isi semua field dengan benar');
            return;
        }

        // Send data to API
        const response = await fetch('api/booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert('Booking berhasil! Kami akan menghubungi Anda untuk konfirmasi.');
            bookingForm.reset();
        } else {
            throw new Error(result.message || 'Terjadi kesalahan saat booking');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
    }
});

// Form validation
function validateForm(formData) {
    return formData.name && formData.phone && formData.service && formData.date && formData.time;
}

// Function to check time slot availability
async function checkTimeSlotAvailability(date, time) {
    try {
        const response = await fetch(`api/check-availability.php?date=${date}&time=${time}`);
        const result = await response.json();
        return result.available;
    } catch (error) {
        console.error('Error checking availability:', error);
        return false;
    }
}

// Function to show error message in time select
function showTimeSelectError(message) {
    timeSelect.innerHTML = `<option value="">${message}</option>`;
    timeSelect.disabled = true;
    setTimeout(() => {
        timeSelect.innerHTML = '<option value="">Pilih Waktu</option>';
        timeSelect.disabled = false;
    }, 3000);
}

// Update available times based on selected date
const dateInput = document.getElementById('date');
dateInput.addEventListener('change', async () => {
    timeSelect.innerHTML = '<option value="">Memeriksa ketersediaan...</option>';
    timeSelect.disabled = true;
    
    const selectedDate = dateInput.value;
    if (!selectedDate) {
        timeSelect.innerHTML = '<option value="">Pilih Waktu</option>';
        timeSelect.disabled = false;
        return;
    }

    try {
        console.log('Mengambil data untuk tanggal:', selectedDate);
        const response = await fetch(`api/check-availability.php?date=${encodeURIComponent(selectedDate)}`);
        
        // Periksa apakah respons adalah JSON yang valid
        const responseText = await response.text();
        let result;
        
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('Gagal mengurai respons JSON:', responseText);
            throw new Error('Format respons tidak valid dari server');
        }

        console.log('API Response:', result);

        if (result.success) {
            timeSelect.innerHTML = '<option value="">Pilih Waktu</option>';
            
            if (result.available_times && result.available_times.length > 0) {
                result.available_times.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    option.textContent = time;
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;
            } else {
                showTimeSelectError('Tidak ada slot tersedia');
            }
        } else {
            showTimeSelectError(result.message || 'Gagal memuat waktu');
        }
    } catch (error) {
        console.error('Error:', error);
        showTimeSelectError('Error: ' + error.message);
    }
});

// Add real-time availability check when time is selected
timeSelect.addEventListener('change', async () => {
    const selectedTime = timeSelect.value;
    const selectedDate = dateInput.value;
    
    if (selectedDate && selectedTime) {
        const isAvailable = await checkTimeSlotAvailability(selectedDate, selectedTime);
        
        if (!isAvailable) {
            alert('Maaf, waktu yang dipilih sudah tidak tersedia. Silakan pilih waktu lain.');
            timeSelect.value = '';
        }
    }
});
