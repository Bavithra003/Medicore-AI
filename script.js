// ================================
// MAIN WEBSITE FUNCTIONS
// ================================

// Smooth Scroll

function scrollSection(id) {

    document.getElementById(id)
    .scrollIntoView({
        behavior: "smooth"
    });

}

// Navbar Active Effect

const navLinks =
document.querySelectorAll("nav a");

navLinks.forEach(link => {

    link.addEventListener("click", () => {

        navLinks.forEach(item => {
            item.classList.remove("active");
        });

        link.classList.add("active");

    });

});

// Loading Animation

window.addEventListener("load", () => {

    document.body.classList.add("loaded");

});

// Emergency Popup

function emergencyAlert() {

    alert(
        "Emergency Ambulance Contacted Successfully!"
    );

}

// Hospital Notification

function showNotification(message) {

    const notification =
    document.createElement("div");

    notification.className = "notification";

    notification.innerText = message;

    document.body.appendChild(notification);

    setTimeout(() => {

        notification.remove();

    }, 3000);

}

// Dynamic Time

function updateTime() {

    const now = new Date();

    const timeString =
    now.toLocaleTimeString();

    const timeElement =
    document.getElementById("liveTime");

    if(timeElement){

        timeElement.innerText =
        timeString;

    }

}

setInterval(updateTime,1000);

// Hospital Counter Animation

const counters =
document.querySelectorAll(".counter");

counters.forEach(counter => {

    counter.innerText = "0";

    const updateCounter = () => {

        const target =
        +counter.getAttribute("data-target");

        const current =
        +counter.innerText;

        const increment =
        target / 100;

        if(current < target){

            counter.innerText =
            `${Math.ceil(current + increment)}`;

            setTimeout(updateCounter,20);

        } else {

            counter.innerText = target;

        }

    };

    updateCounter();

});

// Dark Mode

function toggleDarkMode() {

    document.body.classList.toggle("dark-mode");

}

// Footer Year

const year =
new Date().getFullYear();

const yearBox =
document.getElementById("year");

if(yearBox){

    yearBox.innerText = year;

}

// Scroll Top Button

const topBtn =
document.getElementById("topBtn");

window.onscroll = function(){

    if(document.body.scrollTop > 100 ||
       document.documentElement.scrollTop > 100){

        if(topBtn){

            topBtn.style.display = "block";

        }

    } else {

        if(topBtn){

            topBtn.style.display = "none";

        }

    }

};

function scrollTopPage(){

    window.scrollTo({
        top:0,
        behavior:"smooth"
    });

}

// Random Health Tips

const healthTips = [

    "Drink at least 3 litres of water daily.",
    "Exercise regularly for better health.",
    "Sleep minimum 7 hours daily.",
    "Eat balanced nutritious food.",
    "Avoid stress and practice meditation."

];

function showHealthTip(){

    const random =
    Math.floor(Math.random() *
    healthTips.length);

    const tip =
    document.getElementById("healthTip");

    if(tip){

        tip.innerText =
        healthTips[random];

    }

}

setInterval(showHealthTip,5000);

// Service Card Hover Animation

const cards =
document.querySelectorAll(".service-card");

cards.forEach(card => {

    card.addEventListener("mouseenter", () => {

        card.style.transform =
        "translateY(-10px)";

    });

    card.addEventListener("mouseleave", () => {

        card.style.transform =
        "translateY(0px)";

    });

});

// Hospital Theme Color Switch

function changeTheme(color){

    document.documentElement
    .style.setProperty(
        "--main-color",
        color
    );

}

// Page Loader

window.onload = () => {

    const loader =
    document.getElementById("loader");

    if(loader){

        loader.style.display = "none";

    }

};

// Search Doctors

function searchDoctor(){

    const input =
    document.getElementById("doctorSearch")
    .value.toLowerCase();

    const doctors =
    document.querySelectorAll(".doctor-card");

    doctors.forEach(doctor => {

        const text =
        doctor.innerText.toLowerCase();

        if(text.includes(input)){

            doctor.style.display = "block";

        } else {

            doctor.style.display = "none";

        }

    });

}

// Welcome Message

setTimeout(() => {

    showNotification(
        "Welcome to AI Smart Hospital"
    );

},2000);