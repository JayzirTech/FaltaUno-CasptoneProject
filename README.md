# ⚽ FaltaUno - RIWI Capstone Project

Welcome to **FaltaUno**! The web platform designed to eliminate friction when organizing soccer matches. If you are missing a player to complete tonight's match or are looking for a team to join in your area, FaltaUno connects you in real time.

This project was developed under agile methodologies (Scrum) as part of the Capstone Project at **RIWI**.

---

## 🚀 MVP (Minimum Viable Product) Features

FaltaUno solves the problem of "miscommunication" and last-minute cancellations through 4 main modules (Epics):

1. **Secure Authentication and Profiles:** Registration and login with encrypted passwords on the backend, and profiles where players define their position on the field and contact information.
2. **Main Match Wall:** A space where organizers publish openings (date, time, location, field price, missing spots) and users search for active matches using dynamic filters.
3. **Application System (Matchmaking):** Players apply to available matches, and the organizer has full control to accept or reject them in real time, managing concurrent openings.
4. **Organizer Dashboard:** A centralized view for each user to manage the matches they have created and administer active applications.

---

## 🛠️ Tech Stack

To ensure a smooth experience emulating a native mobile application, the project was built using the following architecture:

* **Frontend:** Vanilla JavaScript (ES6+), HTML5, CSS3 structured under a **SPA (Single Page Application)** architecture with native dynamic routing (no page reloads).
* **Backend:** Node.js (Express) responsible for business logic, route protection, and security hashing.
* **Database:** MySQL. Strictly designed in **Third Normal Form (3NF)** to ensure consistency, avoid redundancy, and guarantee optimal management of concurrent spots.

## 👥 Our Team and Roles (Scrum Methodology)
We are a team of members structured under the agile framework to simulate a real software company:

- **Scrum Master / Technical Lead:** Jira board coordination, blocker removal, and Sprint scope control.
  -Jayzir Martinez

- **Frontend Team (2 Developers):** Layout of dynamic interfaces in the SPA and consumption of server APIs.
  -Guillermo de León Rojano
  -Joel Hernández


- **Backend Team (2 Developers):** Database architecture and normalization, encryption using hashing (Bcrypt), and development of business endpoints.
  -Julian Vanegas
  -Camilo Andrés Meza Vásquez

## 🏁 Git Workflow (Golden Rules)
To keep the repository clean and avoid code conflicts on the remote server, the team follows this strict workflow:

- **Main Branch (main):** Only 100% stable production code.

- **Integration Branch (develop):** Where finished features are merged.

- **Feature Branches (feature/):** Each developer works on their local machine with a clear naming convention linked to Jira (e.g., feature/login-auth or feature/muro-maquetacion).

> [!note]
> ⚠️ **Golden Rule:** Before pushing a feature, the developer must pull the latest changes from develop into their local branch (git pull origin develop), resolve conflicts on their local machine, make the final commit, and then open a Pull Request (PR) to develop for team review.

- **Code Review**
Clone the repository:
  ```bash
  git clone [https://github.com/JayzirTech/FaltaUno-CasptoneProject.git](https://github.com/JayzirTech/FaltaUno-CasptoneProject.git)

> [!note]
> ⚠️ Using `git clone` will only allow you to obtain the project's source code, but you won't be able to run the application because you won't have access to the database. To see the application in action, go to the following link: [FaltaUno - Proyecto Integrador RIWI](https://cambiadigital.co/faltauno-app/). Log in using the email address: **carlos@demo.co** and the password: **faltauno123** to access the application.