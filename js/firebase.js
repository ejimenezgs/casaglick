import { initializeApp, getApp, getApps } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.12.5/firebase-firestore.js";

// Shared Firebase web app configuration used by Casa Glick properties.
// getApps() prevents creating a second Firebase app if another module already initialized it.
const firebaseConfig = {
  apiKey: "AIzaSyBxChWYQYJIfdzCe4HL51x8oGixcvLAxJw",
  authDomain: "drkprty-654ec.firebaseapp.com",
  projectId: "drkprty-654ec",
  storageBucket: "drkprty-654ec.firebasestorage.app",
  messagingSenderId: "17948730429",
  appId: "1:17948730429:web:917b9d10f70439c54c3654",
  measurementId: "G-8TZ70MZQFM"
};

const app = getApps().length ? getApp() : initializeApp(firebaseConfig);
const db = getFirestore(app);

export { app, db };
