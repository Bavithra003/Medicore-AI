// ================================
// APPOINTMENT SYSTEM
// ================================

const appointmentForm =
document.getElementById(
    "appointmentForm"
);

if(appointmentForm){

    appointmentForm
    .addEventListener("submit",
    function(e){

        e.preventDefault();

        const inputs =
        appointmentForm.querySelectorAll(
            "input, select"
        );

        let valid = true;

        inputs.forEach(input => {

            if(input.value === ""){

                valid = false;

            }

        });

        if(valid){

            const token =
            "APT-" +
            Math.floor(
                Math.random() * 9999
            );

            document.getElementById(
                "appointmentMessage"
            ).innerHTML =

            `
            <div class="success-box">
                Appointment Booked Successfully
                <br>
                Your Token:
                <strong>${token}</strong>
            </div>
            `;

            appointmentForm.reset();

        }

        else {

            alert(
                "Please Fill All Fields"
            );

        }

    });

}

// Available Slots

const slots = [

    "10:00 AM",
    "11:00 AM",
    "12:00 PM",
    "02:00 PM",
    "04:00 PM"

];

function loadSlots(){

    const slotBox =
    document.getElementById("slots");

    if(slotBox){

        slots.forEach(slot => {

            const btn =
            document.createElement("button");

            btn.innerText = slot;

            btn.className =
            "slot-btn";

            slotBox.appendChild(btn);

        });

    }

}

loadSlots();