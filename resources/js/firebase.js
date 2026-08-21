import { initializeApp } from 'firebase/app';

const firebaseConfig = {
  apiKey: "AIzaSyBpMnm44oOUbmK-ULuAPJtPUEm-Pdu6aoY",
  authDomain: "maxsqaush.firebaseapp.com",
  projectId: "maxsqaush",
  storageBucket: "maxsqaush.firebasestorage.app",
  messagingSenderId: "741783580155",
  appId: "1:741783580155:web:3a6ad8c0a8c5c65c7fef5e"
};

const firebaseApp = initializeApp(firebaseConfig);

export default firebaseApp;
