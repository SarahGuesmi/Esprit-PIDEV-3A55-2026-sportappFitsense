# FitSense - Smart Fitness, Nutrition & Mental Health Platform
<img width="500" height="500" alt="sport-hero" src="https://github.com/user-attachments/assets/e242ee5d-6c5c-4664-87a9-34c1e6eee43b" />


## Overview

FitSense is a smart platform designed to help users manage their fitness activities, nutrition habits, and mental well-being.

The application allows users to:
- Track workouts and physical activities
- Monitor calories and daily water intake
- Follow personalized nutrition and training programs
- Complete quizzes related to health and fitness
- Manage user profiles and system roles
- Monitor mental health and well-being

FitSense integrates physical health, nutrition management, mental wellness, and learning tools in a single digital platform.

This project was developed as part of the PIDEV – 3rd Year Engineering Program at **Esprit School of Engineering** (Academic Year 2025-2026).

---

## Features

**User Management**
- User registration and authentication
- Role management (Admin, Coach, User)
- Profile management
- Password reset system
- Secure authentication

**Nutrition Management**
- Daily calorie tracking
- Water intake monitoring
- Nutrition goals management
- Healthy recipes consultation
- Nutrition dashboard

**Fitness & Workout Management**
- Workout program management
- Exercise tracking
- Physical activity monitoring
- Personalized fitness recommendations

**Quiz System**
- Health and fitness quizzes
- Question and answer management
- Score calculation
- Educational content

**Mental Health Monitoring**
- Mood tracking
- Stress level monitoring
- Mental well-being evaluation
- Wellness suggestions

**Dashboard**
- Personalized dashboard
- Fitness statistics
- Nutrition progress visualization
- Health analytics

---

## Tech Stack

### Frontend
- HTML5
- CSS3
- JavaScript
- Twig Templates
- Tailwind CSS

### Backend
- Symfony 6.4
- PHP
- Doctrine ORM

### Database
- MySQL

### Tools
- GitHub
- GitHub Education
- Composer

---

## Architecture

FitSense follows a Model-View-Controller (MVC) architecture using the Symfony framework.

**Model**
- Entities managed with Doctrine ORM
- Database interactions and data persistence

**View**
- Twig templates for user interface
- Dynamic UI components with JavaScript and TailwindCSS

**Controller**
- Symfony controllers managing business logic
- Routing and request handling

This architecture ensures scalability, maintainability, and modular development.

---

## Contributors
- Ayari Farah
- Sarra Guesmi
- Ranim Chelly
- Nour Ammar
- Aziz Zarrouk  
  Students of **Esprit School of Engineering**

---

## Academic Context

Developed at **Esprit School of Engineering – Tunisia**

Program: PIDEV – 3rd Year Engineering Program  
Academic Year: 2025-2026

This academic project allows students to apply full-stack development concepts, database design, and modern web technologies while building an innovative digital health platform.

---

## Getting Started

1. **Clone the repository**:  
   `git clone https://github.com/SarahGuesmi/Esprit-PIDEV-3A55-2026-sportappfit.git`

2. **Install dependencies**:  
   Navigate to the project folder and run `composer install` to install PHP/Symfony dependencies.

3. **Configure the database**:  
   - Update `.env` with your MySQL credentials (ex. : `DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name?serverVersion=8.0"`).  
   - Run `php bin/console doctrine:database:create` to create the DB.  
   - Run `php bin/console doctrine:migrations:migrate` to apply migrations.

4. **Start the server**:  
   Run `symfony serve` or `php bin/console server:run` to launch the app locally (usually at http://localhost:8000).

5. **Test**:  
   Access the app in your browser and create an account to explore features.

---

## Acknowledgments

Special thanks to:
- **Esprit School of Engineering** for providing the academic framework and resources.
- The GitHub Education Program for supporting student projects.
