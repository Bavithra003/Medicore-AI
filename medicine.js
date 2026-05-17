// ================================
// MEDICINE REMINDER SYSTEM
// ================================

function setMedicineReminder(){

    const medicine =
    document.getElementById(
        "medicineName"
    ).value;

    const time =
    document.getElementById(
        "medicineTime"
    ).value;

    if(medicine === "" || time === ""){

        alert(
            "Please Fill Medicine Details"
        );

        return;

    }

    const message =
    document.getElementById(
        "medicineMessage"
    );

    message.innerHTML =

    `
    <div class="success-box">
        Reminder Set Successfully
        <br>
        Medicine:
        <strong>${medicine}</strong>
        <br>
        Time:
        <strong>${time}</strong>
    </div>
    `;

    // Browser Notification

    if(Notification.permission ===
       "granted"){

        new Notification(
            "Medicine Reminder Set"
        );

    }

}

// Notification Permission

if("Notification" in window){

    Notification.requestPermission();

}

// Medicine List

const medicines = [

    {
        name:"Paracetamol",
        time:"08:00 AM"
    },

    {
        name:"Vitamin D",
        time:"01:00 PM"
    },

    {
        name:"Insulin",
        time:"08:00 PM"
    }

];

function loadMedicines(){

    const medicineList =
    document.getElementById(
        "medicineList"
    );

    if(medicineList){

        medicines.forEach(item => {

            const div =
            document.createElement("div");

            div.className =
            "medicine-item";

            div.innerHTML =

            `
            <h4>${item.name}</h4>
            <p>${item.time}</p>
            `;

            medicineList.appendChild(div);

        });

    }

}

loadMedicines();