// ================================
// HOSPITAL DASHBOARD
// ================================

// Dashboard Statistics

const dashboardStats = {

    patients : 5421,
    doctors : 152,
    appointments : 2310,
    surgeries : 540

};

// Display Dashboard Stats

function loadDashboard(){

    const patients =
    document.getElementById("patientsCount");

    const doctors =
    document.getElementById("doctorsCount");

    const appointments =
    document.getElementById("appointmentsCount");

    const surgeries =
    document.getElementById("surgeriesCount");

    if(patients){

        patients.innerText =
        dashboardStats.patients;

    }

    if(doctors){

        doctors.innerText =
        dashboardStats.doctors;

    }

    if(appointments){

        appointments.innerText =
        dashboardStats.appointments;

    }

    if(surgeries){

        surgeries.innerText =
        dashboardStats.surgeries;

    }

}

window.onload = loadDashboard;

// Live Ambulance Status

const ambulanceStatus = [

    "Ambulance 1 Available",
    "Ambulance 2 On Emergency",
    "Ambulance 3 Available",
    "Ambulance 4 Busy"

];

function updateAmbulance(){

    const random =
    Math.floor(
        Math.random() *
        ambulanceStatus.length
    );

    const box =
    document.getElementById("ambulanceStatus");

    if(box){

        box.innerText =
        ambulanceStatus[random];

    }

}

setInterval(updateAmbulance,4000);

// Hospital News

const hospitalNews = [

    "Free Health Camp on Sunday",
    "New MRI Scan Facility Added",
    "Cardiology Department Expanded",
    "Emergency ICU Upgraded"

];

function updateNews(){

    const random =
    Math.floor(
        Math.random() *
        hospitalNews.length
    );

    const news =
    document.getElementById("hospitalNews");

    if(news){

        news.innerText =
        hospitalNews[random];

    }

}

setInterval(updateNews,5000);