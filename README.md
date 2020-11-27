# codechef-internship-assessment

Hello this is a programming practice app developed by Vishesh Maheshwari ([theviral_v](https://www.codechef.com/users/theviral_v)) for Codechef Internship Assessment 2021 Round 2.

## Technology Stack:

1. Slim framework PHP
2. Javascript
3. jQuery
4. MySQL

## Different functionalities included in the web app:

1. **Different question tags of codechef**: All question tags on codechef are presented with there problem count and segregated into different categories namely: 

   1. Author
   2. Actual tags
   3. My tags ( user defined personal tags )

   Moreover, you can also sort the tags according to:

   1. Tag Name
   2. Problem Count for each tag<br>

   ![](https://i.ibb.co/ZTFrGwG/Screenshot-from-2020-11-27-22-06-35.png)

2. **Auto-search bar (Multi tag search capabilities):** All tags (author, actual tags and personal tags) can be searched from the auto search bar provided in the app. Moreover the search bar supports multi tag list search, so a question linked to multiple tags can be searched easily.

3. **Questions linked to a tag:** All users once searches/clicked on any tag(s) can see the questions linked to the tag(s) along with:

   1. Author name
   2. Accuracy
   3. Submissions
   4. All tags linked to the question (searched and non-searched both).

   Moreover the questions fetched can be sorted according to:

   1. Number of total submissions
   2. Accuracy of questions<br>

   ![](https://i.ibb.co/L5HfJc5/Screenshot-from-2020-11-27-22-59-05.png)

4. **User Authorisation (using Codechef API):** Users can authorise to the app through OAuth2 linkage of the app with the codechef API.<br>
   ![](https://i.ibb.co/r0HTGN5/Screenshot-from-2020-11-27-23-05-03.png)
   ![](https://i.ibb.co/tM6zsp6/Screenshot-from-2020-11-27-23-07-21.png)

5. **Add new user-defined personal tags:**  Logged in users can add there own personal tags to different questions. The data is updated in backend using AJAX. These tags are only accessible to the owner of the tags and hidden for rest of the users.<br>
   ![](https://i.ibb.co/hsbFCtG/Screenshot-from-2020-11-27-23-11-39.png)
   ![](https://i.ibb.co/k9M8DwG/Screenshot-from-2020-11-27-23-13-35.png)

6. **Delete personal tags:** Logged in users can delete there personal tags created by them. They can either delete them for certain questions or delete them from all the questions forever.<br>
   ![](https://i.ibb.co/H71smLT/Screenshot-from-2020-11-27-23-15-51.png)
   ![](https://i.ibb.co/Sd4p8dR/Screenshot-from-2020-11-27-23-17-43.png)



All the codes and SQL dump files of database used are included in the repository.
