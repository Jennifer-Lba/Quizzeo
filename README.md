#Quizzeo

<!--
//Expliquer le but du projet , ce qu'il fait, pour qui il est fait, les fonctionnalités principales -->

Quizzeo est une application web permettant de gérer des quiz selon différents rôles (Admin, École, Entreprise, Utilisateur).
Le projet a été réalisé en équipe dans le cadre d’un travail collaboratif, avec une architecture MVC simplifiée et une base de données MySQL.

            OBJECTIFS

L’objectif de Quizzeo est de proposer une plateforme complète permettant :
-La gestion des utilisateurs (Admin, École, Entreprise, User)
-La création, modification et suppression de quiz
-L’ajout et la gestion des questions
-La gestion des choix et réponses correctes pour les QCM
-Un système d’authentification sécurisé (hash, session, rôles)
-Un dashboard personnalisé selon le rôle

        Authentification & Sécurité

-Inscription avec vérification des champs
-Connexion avec vérification du mot de passe (password_verify)
-Sessions sécurisées
-Messages d’erreur et de succès via $\_SESSION
-Redirection automatique selon le rôle :
-Admin → /views/admin/dashboard.php
-École → /views/ecole/dashboard.php
-Entreprise → /views/entreprise/dashboard.php
-Autres → /views/user/dashboard.php

        Rôles disponibles

-Admin
-Accès complet
-Gestion des utilisateurs
-Gestion des quiz
-École
-Accès à un dashboard dédié
-Entreprise
-Dashboard entreprise
-Utilisateur
-Dashboard de base

        Gestion des Quiz

Via QuizController.php, nous pouvons
-Créer un quiz
-Modifier un quiz
-Supprimer un quiz
-Lister les quiz

        Gestion des Questions

Via Question.php, nous pouvons
-Ajouter une question (QCM ou text)
-Ajouter plusieurs choix pour un QCM
-Définir la bonne réponse
-Supprimer une question + ses choix
-Récupérer toutes les questions d’un quiz

        INSTALLATION

-cloner le projet avec :
git clone https://github.com/Jennifer-Lba/Quizzeo.git

-importer la bdd
-configurer la connexion a la bdd
-lancer le server PHP

        Travail en équipe

Le projet a été développé en collaboration :
--- Zeinab Hammoud et David Kifouly --> Back-end

--- Jennifer Libla et David Tang --> Front-end

La structure de la base de donnée se trouve dans le fichier shema.sql

Le README reflète notre travail collectif.
