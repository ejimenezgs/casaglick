import { db } from "./firebase.js?v=20260717-contact-rules-v25";
import {
  addDoc,
  collection,
  serverTimestamp
} from "https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js";

const CONTACT_SOURCE = "casaglick.com";
const CONTACT_STATUS = "unread";

const contactForm = document.querySelector("[data-contact-form]");
const contactStatus = document.querySelector("[data-contact-status]");

function getFieldValue(form, fieldName) {
  return String(new FormData(form).get(fieldName) || "").trim();
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function setStatus(message, type = "") {
  if (!contactStatus) return;
  contactStatus.textContent = message;
  contactStatus.dataset.status = type;
}

function validateContactMessage({ name, email, phone, message }) {
  if (name.length < 2) {
    return "Escribe tu nombre completo.";
  }

  if (!isValidEmail(email)) {
    return "Escribe un correo electrónico válido.";
  }

  if (phone.length > 30) {
    return "El teléfono no debe superar 30 caracteres.";
  }

  if (message.length < 2) {
    return "Escribe un mensaje para poder ayudarte.";
  }

  if (message.length > 3000) {
    return "El mensaje no debe superar 3000 caracteres.";
  }

  return "";
}

async function saveContactMessage({ name, email, phone, message }) {
  await addDoc(collection(db, "contactMessages"), {
    name: name.trim(),
    email: email.trim(),
    phone: phone.trim(),
    message: message.trim(),
    source: CONTACT_SOURCE,
    status: CONTACT_STATUS,
    createdAt: serverTimestamp()
  });
}

contactForm?.addEventListener("submit", async (event) => {
  event.preventDefault();

  const submitButton = contactForm.querySelector('[type="submit"]');
  const originalButtonText = submitButton?.textContent || "Enviar";

  const formPayload = {
    name: getFieldValue(contactForm, "name"),
    email: getFieldValue(contactForm, "email"),
    phone: getFieldValue(contactForm, "phone"),
    message: getFieldValue(contactForm, "message")
  };

  const validationMessage = validateContactMessage(formPayload);

  if (validationMessage) {
    setStatus(validationMessage, "error");
    return;
  }

  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = "Enviando...";
  }

  setStatus("Guardando tu mensaje...", "loading");

  try {
    await saveContactMessage(formPayload);
    contactForm.reset();
    setStatus("Gracias. Recibimos tu mensaje y te contactaremos pronto.", "success");
  } catch (error) {
    console.error("No se pudo guardar el mensaje de contacto en Firebase:", error);
    setStatus("No se pudo enviar el mensaje. Intenta de nuevo en unos minutos.", "error");
  } finally {
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = originalButtonText;
    }
  }
});
