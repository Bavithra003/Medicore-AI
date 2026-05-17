// ================================
// QUEUE TRACKING SYSTEM
// ================================

let currentToken = 101;

function updateQueue(){

    currentToken++;

    const tokenBox =
    document.getElementById(
        "tokenNumber"
    );

    if(tokenBox){

        tokenBox.innerText =
        "A-" + currentToken;

    }

}

setInterval(updateQueue,5000);

// Waiting Time

function updateWaitingTime(){

    const wait =
    Math.floor(
        Math.random() * 30
    ) + 5;

    const waitBox =
    document.getElementById(
        "waitingTime"
    );

    if(waitBox){

        waitBox.innerText =
        wait + " Minutes";

    }

}

setInterval(updateWaitingTime,4000);

// Queue Notification

function queueNotification(){

    const notifications = [

        "Patient A-105 Please Proceed",
        "Emergency Case Arrived",
        "Doctor Available Now",
        "Queue Running Smoothly"

    ];

    const random =
    Math.floor(
        Math.random() *
        notifications.length
    );

    const queueBox =
    document.getElementById(
        "queueNotification"
    );

    if(queueBox){

        queueBox.innerText =
        notifications[random];

    }

}

setInterval(queueNotification,6000);