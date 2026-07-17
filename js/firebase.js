import { initializeApp, getApp, getApps } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js";

// Configuracion publica de Firebase compartida con panel.casaglick.com y shop.casaglick.com.
// getApps() evita crear una segunda instancia si otro modulo ya inicializo Firebase.
const firebaseConfig = {
  apiKey: "AIzaSyBu4DJAxE_mn7MsVZNa-PMu-WNuFNsEPGU",
  authDomain: "casaglick-439b2.firebaseapp.com",
  projectId: "casaglick-439b2",
  storageBucket: "casaglick-439b2.firebasestorage.app",
  messagingSenderId: "888985882616",
  appId: "1:888985882616:web:92464a1e63ee74ef3fd00d",
  measurementId: "G-5HWE12CWPW"
};

const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
const db = getFirestore(app);

export { app, db };
