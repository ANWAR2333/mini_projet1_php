-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 19 oct. 2026 à 15:11
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_factures`
--

DROP DATABASE IF EXISTS `gestion_factures`;
CREATE DATABASE `gestion_factures` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gestion_factures`;

-- --------------------------------------------------------

--
-- Structure de la table `agent`
--

CREATE TABLE `agent` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `anomalie`
--

CREATE TABLE `anomalie` (
  `id` int(11) NOT NULL,
  `id_consommation` int(11) NOT NULL,
  `service` enum('eau','electricite') NOT NULL,
  `type` enum('index_inferieur','depasse_seuil') NOT NULL,
  `index_precedent` decimal(10,2) NOT NULL,
  `index_saisi` decimal(10,2) NOT NULL,
  `index_corrige` decimal(10,2) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `date_detection` datetime DEFAULT current_timestamp(),
  `date_correction` datetime DEFAULT NULL,
  `statut` enum('a_traiter','corrigee') DEFAULT 'a_traiter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `num_compteur_eau` varchar(50) DEFAULT NULL,
  `num_compteur_elec` varchar(50) DEFAULT NULL,
  `index_initial_eau` decimal(10,2) NOT NULL DEFAULT 0.00,
  `index_initial_elec` decimal(10,2) NOT NULL DEFAULT 0.00,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consommation`
--

CREATE TABLE `consommation` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `mois` tinyint(4) NOT NULL,
  `annee` smallint(6) NOT NULL,
  `index_eau` decimal(10,2) NOT NULL,
  `index_elec` decimal(10,2) NOT NULL,
  `photo_eau` varchar(255) DEFAULT NULL,
  `photo_elec` varchar(255) DEFAULT NULL,
  `date_saisie` datetime DEFAULT current_timestamp(),
  `statut` enum('normale','anomalie','corrigee') DEFAULT 'normale',
  CONSTRAINT `chk_mois` CHECK (`mois` BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consommation_annuelle`
--

CREATE TABLE `consommation_annuelle` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_agent` int(11) NOT NULL,
  `conso_eau` decimal(10,2) NOT NULL,
  `conso_elec` decimal(10,2) NOT NULL,
  `annee` smallint(6) NOT NULL,
  `date_saisie` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `id` int(11) NOT NULL,
  `numero` varchar(30) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_consommation` int(11) NOT NULL,
  `date_facture` date NOT NULL,
  `periode` varchar(20) NOT NULL,
  `conso_eau` decimal(10,2) DEFAULT NULL,
  `conso_elec` decimal(10,2) DEFAULT NULL,
  `montant_ht` decimal(10,2) DEFAULT NULL,
  `tva` decimal(10,2) DEFAULT NULL,
  `montant_ttc` decimal(10,2) DEFAULT NULL,
  `statut` enum('impayee','payee') DEFAULT 'impayee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur`
--

CREATE TABLE `fournisseur` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_reclamation` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `lue` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `id` int(11) NOT NULL,
  `id_facture` int(11) NOT NULL,
  `date_paiement` date NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `mode` enum('especes','carte','virement') DEFAULT 'especes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametre`
--

CREATE TABLE `parametre` (
  `id` int(11) NOT NULL DEFAULT 1,
  `seuil_eau` decimal(10,2) DEFAULT 50.00,
  `seuil_elec` decimal(10,2) DEFAULT 500.00,
  `taux_tva` decimal(4,2) DEFAULT 0.18
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `parametre`
--

INSERT INTO `parametre` (`id`, `seuil_eau`, `seuil_elec`, `taux_tva`) VALUES
(1, 50.00, 500.00, 0.18);

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_facture` int(11) DEFAULT NULL,
  `type` enum('fuite_externe','fuite_interne','facture','autre') NOT NULL,
  `autre_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_reclamation` datetime DEFAULT current_timestamp(),
  `statut` enum('en_attente','en_cours','traitee') DEFAULT 'en_attente',
  `reponse` text DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `agent`
--
ALTER TABLE `agent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `anomalie`
--
ALTER TABLE `anomalie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_consommation` (`id_consommation`);

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `num_compteur_eau` (`num_compteur_eau`),
  ADD UNIQUE KEY `num_compteur_elec` (`num_compteur_elec`);

--
-- Index pour la table `consommation`
--
ALTER TABLE `consommation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_client` (`id_client`,`mois`,`annee`);

--
-- Index pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_client` (`id_client`,`annee`),
  ADD KEY `id_agent` (`id_agent`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD UNIQUE KEY `id_consommation` (`id_consommation`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_reclamation` (`id_reclamation`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Index pour la table `parametre`
--
ALTER TABLE `parametre`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_client` (`id_client`),
  ADD KEY `id_facture` (`id_facture`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `agent`
--
ALTER TABLE `agent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `anomalie`
--
ALTER TABLE `anomalie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consommation`
--
ALTER TABLE `consommation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `facture`
--
ALTER TABLE `facture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `anomalie`
--
ALTER TABLE `anomalie`
  ADD CONSTRAINT `anomalie_ibfk_1` FOREIGN KEY (`id_consommation`) REFERENCES `consommation` (`id`);

--
-- Contraintes pour la table `consommation`
--
ALTER TABLE `consommation`
  ADD CONSTRAINT `consommation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`);

--
-- Contraintes pour la table `consommation_annuelle`
--
ALTER TABLE `consommation_annuelle`
  ADD CONSTRAINT `consommation_annuelle_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `consommation_annuelle_ibfk_2` FOREIGN KEY (`id_agent`) REFERENCES `agent` (`id`);

--
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `facture_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `facture_ibfk_2` FOREIGN KEY (`id_consommation`) REFERENCES `consommation` (`id`);

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamation` (`id`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id`);

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `client` (`id`),
  ADD CONSTRAINT `reclamation_ibfk_2` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id`);

--
-- ======================= DONNÉES DE TEST (section 4 du cahier des charges) =======================
-- Fournisseur : fournisseur@ecohouse.ma / Four#2026
-- 5 clients (mot de passe Client#2026) avec leur consommation et facture de septembre 2026
--
INSERT INTO `fournisseur` (`id`,`nom`,`email`,`mot_de_passe`,`adresse`,`telephone`) VALUES
(1,'Amendis','fournisseur@ecohouse.ma','$2y$10$ChpC9FQkwmsK1g2lwzWIl.Vjvf3vPkSq9HOGVdkG6i5kNHA45i3Jy','Av. Abdelkhalek Torres, Tétouan','0539000000');
INSERT INTO `client` (`id`,`nom`,`prenom`,`adresse`,`telephone`,`email`,`mot_de_passe`,`num_compteur_eau`,`num_compteur_elec`,`index_initial_eau`,`index_initial_elec`) VALUES
(1,'Bennani','Salma','12 rue Mohammed V, Tétouan','0612345601','salma@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi','EAU-0001','ELEC-0001',1231.00,8320.00),
(2,'ElAmrani','Youssef','5 avenue des FAR, Tétouan','0612345602','youssef@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi','EAU-0002','ELEC-0002',1238.00,6070.00),
(3,'Berrada','Nadia','27 rue Al Massira, Tétouan','0612345603','nadia@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi','EAU-0003','ELEC-0003',632.00,3005.00),
(4,'Tazi','Khadija','8 rue Ibn Battouta, Tétouan','0612345604','khadija@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi','EAU-0004','ELEC-0004',400.00,4960.00),
(5,'Jemaoui','Mouad','33 boulevard Hassan II, Tétouan','0612345605','mouad@gmail.com','$2y$10$ymsVMdZzJ2FkrtOvSG8xy.hi3jUrQ.rtqfoLG9AmgIQo1sB6/WOhi','EAU-0005','ELEC-0005',828.00,2140.00);
INSERT INTO `consommation` (`id`,`id_client`,`mois`,`annee`,`index_eau`,`index_elec`,`photo_eau`,`photo_elec`,`date_saisie`,`statut`) VALUES
(1,1,9,2026,1245.00,8430.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(2,2,9,2026,1250.00,6200.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(3,3,9,2026,640.00,3100.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(4,4,9,2026,410.00,5100.00,NULL,NULL,'2026-09-24 10:00:00','normale'),
(5,5,9,2026,850.00,2300.00,NULL,NULL,'2026-09-24 10:00:00','normale');
INSERT INTO `facture` (`id`,`numero`,`id_client`,`id_consommation`,`date_facture`,`periode`,`conso_eau`,`conso_elec`,`montant_ht`,`tva`,`montant_ttc`,`statut`) VALUES
(1,'F-0926-0001',1,1,'2026-09-30','09/2026',14.00,110.00,204.66,36.84,241.50,'payee'),
(2,'F-0926-0002',2,2,'2026-09-30','09/2026',12.00,130.00,208.28,37.49,245.77,'impayee'),
(3,'F-0926-0003',3,3,'2026-09-30','09/2026',8.00,95.00,137.02,24.66,161.68,'impayee'),
(4,'F-0926-0004',4,4,'2026-09-30','09/2026',10.00,140.00,202.70,36.49,239.19,'impayee'),
(5,'F-0926-0005',5,5,'2026-09-30','09/2026',22.00,160.00,417.56,75.16,492.72,'impayee');
INSERT INTO `paiement` (`id`,`id_facture`,`date_paiement`,`montant`,`mode`) VALUES
(1,1,'2026-10-02',241.50,'especes');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
