// ================================
// AI SYMPTOM CHECKER CHATBOT
// ================================

function sendMessage(){

    const input =
    document.getElementById("userMessage");

    const message =
    input.value.trim();

    if(message === "") return;

    const chatArea =
    document.getElementById("chatArea");

    // User Message

    const userDiv =
    document.createElement("div");

    userDiv.className = "user-message";

    userDiv.innerHTML =
    `<strong>You:</strong> ${message}`;

    chatArea.appendChild(userDiv);

    // AI Response

    let response = "";

    const lower =
    message.toLowerCase();

    if(lower.includes("fever")){

        response =
        "Possible Viral Fever. Drink water and consult physician.";

    }

    else if(lower.includes("headache")){

        response =
        "Possible Migraine or Stress Headache.";

    }

    else if(lower.includes("cold")){

        response =
        "Symptoms indicate common cold.";

    }

    else if(lower.includes("stomach")){

        response =
        "Possible gastric problem detected.";

    }

    else if(lower.includes("chest pain")){

        response =
        "Chest pain detected. Immediate emergency care suggested.";

    }

    else if(lower.includes("skin")){

        response =
        "Consult Dermatology Department.";

    }

    else if(lower.includes("bone")){

        response =
        "Orthopedic consultation recommended.";

    }

    else {

        response =
        "Please consult hospital specialist doctor.";

    }

    setTimeout(() => {

        const botDiv =
        document.createElement("div");

        botDiv.className =
        "bot-message";

        botDiv.innerHTML =
        `<strong>AI:</strong> ${response}`;

        chatArea.appendChild(botDiv);

        chatArea.scrollTop =
        chatArea.scrollHeight;

    },1000);

    input.value = "";

}

// Enter Key Support

document.addEventListener("keypress",
function(e){

    if(e.key === "Enter"){

        sendMessage();

    }

});