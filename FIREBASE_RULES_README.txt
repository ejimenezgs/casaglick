CASA GLICK - CONTACT FORM FIREBASE RULES

The form code saves messages to Firestore collection:
contactMessages

The browser error "permission-denied" means the web code is reaching Firebase correctly, but Firestore rules are blocking the create operation.

To fix it:
1. Open Firebase Console.
2. Go to Firestore Database > Rules.
3. Paste the match block from firestore-contactMessages.rules inside:
   service cloud.firestore {
     match /databases/{database}/documents {
       // paste here, before catch-all rules
     }
   }
4. Publish rules.
5. Hard refresh casaglick.com and test the form again.

Important:
- Do not add extra fields to the document, because the rules use hasOnly.
- The source must be exactly: casaglick.com
- The status must be exactly: unread
