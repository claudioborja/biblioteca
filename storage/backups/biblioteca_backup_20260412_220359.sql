/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: biblioteca
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user_action` (`user_id`,`action`),
  KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_date` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES
(1,1,'mail_send_success','emails',4,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Prueba SMTP — Biblioteca\",\"source\":\"smtp_test\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 13:18:21'),
(2,1,'mail_send_failed','emails',5,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Prueba SMTP — Biblioteca\",\"source\":\"smtp_test\",\"error\":\"Autenticación SMTP fallida: 535-5.7.8 Username and Password not accepted. For more information, go to\\r\\n535 5.7.8  https://support.google.com/mail/?p=BadCredentials 71dfb90a1353d-56f3b9d1a78sm6099070e0c.18 - gsmtp\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 13:18:52'),
(3,NULL,'mail_send_success','emails',6,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Verifica tu correo para activar tu cuenta\",\"source\":\"queue\"}','cli','cli','2026-04-12 14:21:56'),
(4,NULL,'mail_send_success','emails',7,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Verifica tu correo para activar tu cuenta\",\"source\":\"register_verify_immediate\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 14:31:27'),
(5,1,'user_role_changed','users',192,'{\"role\":\"librarian\",\"user_type\":\"staff\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:30'),
(6,1,'mail_role_change_queued','emails',8,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":8}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:30'),
(7,1,'mail_send_success','emails',8,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:32'),
(8,1,'user_role_changed','users',192,'{\"role\":\"user\",\"user_type\":\"student\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:41'),
(9,1,'mail_role_change_queued','emails',9,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":9}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:41'),
(10,1,'mail_send_success','emails',9,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:43'),
(11,1,'user_role_changed','users',192,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"librarian\",\"user_type\":\"staff\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:49'),
(12,1,'mail_role_change_queued','emails',10,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":10}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:49'),
(13,1,'mail_send_success','emails',10,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:39:52'),
(14,1,'user_role_changed','users',192,'{\"role\":\"librarian\",\"user_type\":\"staff\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:43:45'),
(15,1,'mail_role_change_queued','emails',11,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":11}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:43:45'),
(16,NULL,'mail_send_success','emails',11,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:44:28'),
(17,1,'user_role_changed','users',192,'{\"role\":\"user\",\"user_type\":\"student\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:07'),
(18,1,'mail_role_change_queued','emails',12,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":12}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:07'),
(19,1,'user_role_changed','users',192,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"librarian\",\"user_type\":\"staff\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:09'),
(20,1,'mail_role_change_queued','emails',13,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":13}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:09'),
(21,1,'user_role_changed','users',192,'{\"role\":\"librarian\",\"user_type\":\"staff\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:42'),
(22,1,'mail_role_change_queued','emails',14,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":14}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:50:42'),
(23,NULL,'mail_send_success','emails',13,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:51:59'),
(24,NULL,'mail_send_success','emails',14,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:52:02'),
(25,NULL,'mail_send_success','emails',12,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:52:06'),
(26,NULL,'mail_send_success','emails',5,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Prueba SMTP — Biblioteca\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:52:10'),
(27,1,'user_role_changed','users',192,'{\"role\":\"user\",\"user_type\":\"student\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:53:25'),
(28,1,'mail_role_change_queued','emails',15,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":15}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 16:53:25'),
(29,NULL,'mail_send_success','emails',15,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 16:54:18'),
(30,1,'user_role_changed','users',192,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:13'),
(31,1,'mail_role_change_queued','emails',16,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":16}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:13'),
(32,1,'user_role_changed','users',192,'{\"role\":\"user\",\"user_type\":\"student\"}','{\"role\":\"librarian\",\"user_type\":\"staff\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:17'),
(33,1,'mail_role_change_queued','emails',17,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":17}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:17'),
(34,1,'user_role_changed','users',192,'{\"role\":\"librarian\",\"user_type\":\"staff\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:20'),
(35,1,'mail_role_change_queued','emails',18,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":18}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:03:20'),
(36,NULL,'mail_send_success','emails',16,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 17:05:04'),
(37,NULL,'mail_send_success','emails',17,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 17:05:06'),
(38,NULL,'mail_send_success','emails',18,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 17:05:08'),
(39,NULL,'mail_send_success','emails',19,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"[TEST][HIGH] trigger worker\",\"source\":\"queue\"}','cli','cli','2026-04-12 17:06:36'),
(40,1,'user_role_changed','users',3,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"estudiante1@biblioteca.local\",\"target_name\":\"Carlos Hernández Ruiz d\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:49:14'),
(41,1,'mail_role_change_queued','emails',20,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":20}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:49:14'),
(42,NULL,'mail_send_success','emails',20,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 17:50:04'),
(43,1,'user_role_changed','users',192,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"librarian\",\"user_type\":\"staff\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:56:45'),
(44,1,'mail_role_change_queued','emails',21,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":21}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:56:45'),
(45,1,'user_role_changed','users',192,'{\"role\":\"librarian\",\"user_type\":\"staff\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:57:10'),
(46,1,'mail_role_change_queued','emails',22,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":22}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 17:57:10'),
(47,NULL,'mail_send_success','emails',21,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:00:04'),
(48,NULL,'mail_send_success','emails',22,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:00:05'),
(49,NULL,'mail_send_success','emails',23,NULL,'{\"to_email\":\"admin@biblioteca.local\",\"subject\":\"[LOAN_REMINDER][#15] Recordatorio de vencimiento\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:49'),
(50,NULL,'mail_send_success','emails',24,NULL,'{\"to_email\":\"admin@biblioteca.local\",\"subject\":\"[LOAN_REMINDER][#39] Recordatorio de vencimiento\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:51'),
(51,NULL,'mail_send_success','emails',25,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_REMINDER][#8] Recordatorio de vencimiento\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:52'),
(52,NULL,'mail_send_success','emails',26,NULL,'{\"to_email\":\"admin@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#7] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:53'),
(53,NULL,'mail_send_success','emails',27,NULL,'{\"to_email\":\"admin@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#31] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:55'),
(54,NULL,'mail_send_success','emails',28,NULL,'{\"to_email\":\"admin@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#51] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:56'),
(55,NULL,'mail_send_success','emails',29,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#26] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:57'),
(56,NULL,'mail_send_success','emails',30,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#35] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:01:59'),
(57,NULL,'mail_send_success','emails',31,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#45] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:00'),
(58,NULL,'mail_send_success','emails',32,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#49] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:02'),
(59,NULL,'mail_send_success','emails',33,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#6] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:04'),
(60,NULL,'mail_send_success','emails',34,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#14] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:06'),
(61,NULL,'mail_send_success','emails',35,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#21] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:07'),
(62,NULL,'mail_send_success','emails',36,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE][#47] Prestamo vencido\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:10'),
(63,NULL,'mail_send_success','emails',37,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#26] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:12'),
(64,NULL,'mail_send_success','emails',38,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#35] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:13'),
(65,NULL,'mail_send_success','emails',39,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#45] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:14'),
(66,NULL,'mail_send_success','emails',40,NULL,'{\"to_email\":\"docente@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#49] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:15'),
(67,NULL,'mail_send_success','emails',41,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#6] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:17'),
(68,NULL,'mail_send_success','emails',42,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#14] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:02:19'),
(69,NULL,'mail_send_success','emails',43,NULL,'{\"to_email\":\"estudiante1@biblioteca.local\",\"subject\":\"[LOAN_OVERDUE_SECOND][#47] Segunda notificacion de mora\",\"source\":\"queue\"}','cli','cli','2026-04-12 18:03:13'),
(70,1,'user_role_changed','users',192,'{\"role\":\"teacher\",\"user_type\":\"teacher\"}','{\"role\":\"user\",\"user_type\":\"student\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 21:33:27'),
(71,1,'mail_role_change_queued','emails',44,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":44}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 21:33:27'),
(72,NULL,'mail_send_success','emails',44,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 21:35:04'),
(73,1,'user_role_changed','users',192,'{\"role\":\"user\",\"user_type\":\"student\"}','{\"role\":\"teacher\",\"user_type\":\"teacher\",\"target_email\":\"info@softecsa.com\",\"target_name\":\"CLAUDIO XAVIER BORJA SALTOS\"}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 21:51:30'),
(74,1,'mail_role_change_queued','emails',45,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"user_role_change\",\"queue_id\":45}','127.0.0.1','Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0','2026-04-12 21:51:30'),
(75,NULL,'mail_send_success','emails',45,NULL,'{\"to_email\":\"info@softecsa.com\",\"subject\":\"Tu rol en Biblioteca fue actualizado\",\"source\":\"queue\"}','cli','cli','2026-04-12 21:55:04');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `fine_per_day_override` decimal(5,2) DEFAULT NULL,
  `loan_days_override` tinyint(4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Literatura','literatura','Novelas, cuentos, poesía, teatro y obras literarias en general',NULL,NULL,'2026-04-11 14:35:46'),
(2,'Ciencias Naturales','ciencias-naturales','Biología, química, física, astronomía y ciencias de la tierra',NULL,NULL,'2026-04-11 14:35:46'),
(4,'Historia','historia','Historia universal, nacional y regional',NULL,NULL,'2026-04-11 14:35:46'),
(5,'Matemáticas','matematicas','Álgebra, geometría, cálculo, estadística y matemáticas aplicadas',NULL,NULL,'2026-04-11 14:35:46'),
(6,'Tecnología','tecnologia','Informática, programación, ingeniería y tecnologías emergentes',NULL,NULL,'2026-04-11 14:35:46'),
(7,'Arte y Cultura','arte-y-cultura','Pintura, escultura, música, cine, fotografía y artes escénicas',1.00,3,'2026-04-11 14:35:46'),
(8,'Filosofía','filosofia','Filosofía clásica, moderna, contemporánea y ética',NULL,NULL,'2026-04-11 14:35:46'),
(9,'Psicología','psicologia','Psicología clínica, social, educativa y del desarrollo',NULL,NULL,'2026-04-11 14:35:46'),
(10,'Educación','educacion','Pedagogía, didáctica, formación docente y sistemas educativos',NULL,NULL,'2026-04-11 14:35:46'),
(11,'Idiomas','idiomas','Gramática, diccionarios, aprendizaje de lenguas extranjeras',NULL,NULL,'2026-04-11 14:35:46'),
(12,'Derecho','derecho','Legislación, derecho civil, penal, constitucional y laboral',NULL,NULL,'2026-04-11 14:35:46'),
(13,'Economía y Finanzas','economia-y-finanzas','Microeconomía, macroeconomía, contabilidad y finanzas personales',NULL,NULL,'2026-04-11 14:35:46'),
(14,'Salud y Medicina','salud-y-medicina','Medicina general, enfermería, nutrición y salud pública',NULL,NULL,'2026-04-11 14:35:46'),
(16,'Geografía','geografia','Geografía física, humana, atlas y cartografía',NULL,NULL,'2026-04-11 14:35:46'),
(17,'Enciclopedias','enciclopedias','Obras de referencia general, enciclopedias y almanques',NULL,NULL,'2026-04-11 14:35:46'),
(18,'Infantil y Juvenil','infantil-y-juvenil','Libros para niños y jóvenes, cuentos ilustrados y fábulas',NULL,NULL,'2026-04-11 14:35:46'),
(19,'Biografías','biografias','Autobiografías, memorias y biografías de personajes notables',NULL,NULL,'2026-04-11 14:35:46'),
(20,'Religión y Espiritualidad','religion-y-espiritualidad','Teología, estudios religiosos comparados y espiritualidad',NULL,NULL,'2026-04-11 14:35:46');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `digital_access_log`
--

DROP TABLE IF EXISTS `digital_access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `digital_access_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('view','download') NOT NULL DEFAULT 'view',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_digital_access_user` (`user_id`),
  KEY `idx_digital_access_created` (`created_at`),
  KEY `idx_digital_access_resource` (`resource_id`),
  CONSTRAINT `fk_digital_access_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_digital_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `digital_access_log`
--

LOCK TABLES `digital_access_log` WRITE;
/*!40000 ALTER TABLE `digital_access_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `digital_access_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `to_email` varchar(150) NOT NULL,
  `to_name` varchar(150) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `body_html` mediumtext NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(4) NOT NULL DEFAULT 0,
  `scheduled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_queue_status_scheduled` (`status`,`scheduled_at`),
  KEY `idx_email_queue_dispatch` (`status`,`priority`,`scheduled_at`,`attempts`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
INSERT INTO `email_queue` VALUES
(1,'info@softecsa.com','Prueba SMTP','Prueba SMTP — Biblioteca',5,'<h2>Correo de prueba</h2><p>El servicio SMTP está configurado y funcionando correctamente.</p>','Correo de prueba\nEl servicio SMTP está configurado y funcionando correctamente.','sent',0,'2026-04-12 12:04:46','2026-04-12 13:04:49',NULL,'2026-04-12 13:04:46'),
(2,'info@softecsa.com','Prueba SMTP','Prueba SMTP — Biblioteca',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Prueba SMTP - Biblioteca</title>\n</head>\n<body style=\"margin:0;padding:0;background:#f3f6fb;font-family:Segoe UI,Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Prueba del canal de correo</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f3f6fb;padding:24px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;\">\n          <tr>\n            <td style=\"background:linear-gradient(135deg,#0f172a,#1e293b);padding:20px 24px;color:#ffffff;\">\n              <div style=\"font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;\">Sistema Biblioteca</div>\n              <div style=\"font-size:24px;line-height:1.3;font-weight:700;margin-top:6px;\">Prueba SMTP - Biblioteca</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td style=\"padding:24px;\">\n              <p style=\"margin:0 0 16px;font-size:15px;line-height:1.6;color:#334155;\">Este mensaje confirma que el sistema puede enviar notificaciones correctamente.</p>\n              <div style=\"font-size:15px;line-height:1.65;color:#0f172a;\">\n                <p>La conexion SMTP, autenticacion y entrega del mensaje se completaron.</p><p><strong>Fecha de prueba:</strong> 2026-04-12 12:10:24</p><p><strong>Destinatario:</strong> info@softecsa.com</p>\n              </div>\n            </td>\n          </tr>\n\n          <tr>\n            <td style=\"padding:16px 24px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.6;color:#64748b;\">\n              <div>Biblioteca - Mensajeria automatica</div>\n              <div>2026</div>\n              <div>Correo de prueba generado desde Configuracion &gt; Correo SMTP.</div>\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Prueba SMTP - Biblioteca\n========================\n\nEste mensaje confirma que el sistema puede enviar notificaciones correctamente.\n\nLa conexion SMTP, autenticacion y entrega del mensaje se completaron.\nFecha de prueba: 2026-04-12 12:10:24\nDestinatario: info@softecsa.com\n\nBiblioteca - Mensajeria automatica\nCorreo de prueba generado desde Configuracion > Correo SMTP.','sent',0,'2026-04-12 12:10:24','2026-04-12 13:10:26',NULL,'2026-04-12 13:10:24'),
(3,'info@softecsa.com','Prueba SMTP','Prueba SMTP — Biblioteca',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Prueba SMTP - Biblioteca</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Prueba del canal de correo</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:linear-gradient(135deg,#1d4ed8,#0f172a);padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Prueba SMTP - Biblioteca</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Este mensaje confirma que el sistema puede enviar notificaciones correctamente.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>La conexion SMTP, autenticacion y entrega del mensaje se completaron.</p><p><strong>Fecha de prueba:</strong> 2026-04-12 12:12:39</p><p><strong>Destinatario:</strong> info@softecsa.com</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Correo de prueba generado desde Configuracion &gt; Correo SMTP.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Prueba SMTP - Biblioteca\n========================\n\nEste mensaje confirma que el sistema puede enviar notificaciones correctamente.\n\nLa conexion SMTP, autenticacion y entrega del mensaje se completaron.\nFecha de prueba: 2026-04-12 12:12:39\nDestinatario: info@softecsa.com\n\nBiblioteca - Mensajeria automatica\nCorreo de prueba generado desde Configuracion > Correo SMTP.','sent',0,'2026-04-12 12:12:39','2026-04-12 13:12:42',NULL,'2026-04-12 13:12:39'),
(4,'info@softecsa.com','Prueba SMTP','Prueba SMTP — Biblioteca',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Prueba SMTP - Biblioteca</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Prueba del canal de correo</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Prueba SMTP - Biblioteca</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Este mensaje confirma que el sistema puede enviar notificaciones correctamente.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>La conexion SMTP, autenticacion y entrega del mensaje se completaron.</p><p><strong>Fecha de prueba:</strong> 2026-04-12 12:18:18</p><p><strong>Destinatario:</strong> info@softecsa.com</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Correo de prueba generado desde Configuracion &gt; Correo SMTP.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Prueba SMTP - Biblioteca\n========================\n\nEste mensaje confirma que el sistema puede enviar notificaciones correctamente.\n\nLa conexion SMTP, autenticacion y entrega del mensaje se completaron.\nFecha de prueba: 2026-04-12 12:18:18\nDestinatario: info@softecsa.com\n\nBiblioteca - Mensajeria automatica\nCorreo de prueba generado desde Configuracion > Correo SMTP.','sent',0,'2026-04-12 12:18:18','2026-04-12 13:18:21',NULL,'2026-04-12 13:18:18'),
(5,'info@softecsa.com','Prueba SMTP','Prueba SMTP — Biblioteca',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Prueba SMTP - Biblioteca</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Prueba del canal de correo</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Prueba SMTP - Biblioteca</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Este mensaje confirma que el sistema puede enviar notificaciones correctamente.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>La conexion SMTP, autenticacion y entrega del mensaje se completaron.</p><p><strong>Fecha de prueba:</strong> 2026-04-12 12:18:51</p><p><strong>Destinatario:</strong> info@softecsa.com</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Correo de prueba generado desde Configuracion &gt; Correo SMTP.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Prueba SMTP - Biblioteca\n========================\n\nEste mensaje confirma que el sistema puede enviar notificaciones correctamente.\n\nLa conexion SMTP, autenticacion y entrega del mensaje se completaron.\nFecha de prueba: 2026-04-12 12:18:51\nDestinatario: info@softecsa.com\n\nBiblioteca - Mensajeria automatica\nCorreo de prueba generado desde Configuracion > Correo SMTP.','sent',0,'2026-04-12 16:51:19','2026-04-12 16:52:10',NULL,'2026-04-12 13:18:51'),
(6,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Verifica tu correo para activar tu cuenta',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Verificación de correo</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Activa tu cuenta de Biblioteca</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Verificación de correo</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Tu cuenta fue creada correctamente. Falta un último paso para activarla.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Para activar tu cuenta, verifica tu correo haciendo clic en el siguiente enlace:</p><p style=\"margin:18px 0;\"><a href=\"/biblioteca/verify-email/95b7c6aac9283712a90acfd1235dc8efb8b933cd76b5d58552d756d20a9e5006?email=info%40softecsa.com\" style=\"display:inline-block;background:#1e3a8a;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;\">Verificar mi correo</a></p><p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p><p style=\"word-break:break-all;\">/biblioteca/verify-email/95b7c6aac9283712a90acfd1235dc8efb8b933cd76b5d58552d756d20a9e5006?email=info%40softecsa.com</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Este enlace expira en 24 horas.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Verificación de correo\n======================\n\nTu cuenta fue creada correctamente. Falta un último paso para activarla.\n\nAbre este enlace para verificar tu correo: /biblioteca/verify-email/95b7c6aac9283712a90acfd1235dc8efb8b933cd76b5d58552d756d20a9e5006?email=info%40softecsa.com\n\nBiblioteca - Mensajeria automatica\nEste enlace expira en 24 horas.','sent',0,'2026-04-12 13:20:32','2026-04-12 14:21:56',NULL,'2026-04-12 14:20:32'),
(7,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Verifica tu correo para activar tu cuenta',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Verificación de correo</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Activa tu cuenta de Biblioteca</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Verificación de correo</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Tu cuenta fue creada correctamente. Falta un último paso para activarla.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Para activar tu cuenta, verifica tu correo haciendo clic en el siguiente enlace:</p><p style=\"margin:18px 0;\"><a href=\"http://localhost/biblioteca/verify-email/a7df755885ce986c014a2e12350b9af15af3d8ff21bc625bcb3b2cb86a4c8d65?email=info%40softecsa.com\" style=\"display:inline-block;background:#1e3a8a;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:8px;font-weight:600;\">Verificar mi correo</a></p><p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p><p style=\"word-break:break-all;\">http://localhost/biblioteca/verify-email/a7df755885ce986c014a2e12350b9af15af3d8ff21bc625bcb3b2cb86a4c8d65?email=info%40softecsa.com</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Este enlace expira en 24 horas.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Verificación de correo\n======================\n\nTu cuenta fue creada correctamente. Falta un último paso para activarla.\n\nAbre este enlace para verificar tu correo: http://localhost/biblioteca/verify-email/a7df755885ce986c014a2e12350b9af15af3d8ff21bc625bcb3b2cb86a4c8d65?email=info%40softecsa.com\n\nBiblioteca - Mensajeria automatica\nEste enlace expira en 24 horas.','sent',0,'2026-04-12 13:31:24','2026-04-12 14:31:27',NULL,'2026-04-12 14:31:24'),
(8,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:39:30','2026-04-12 16:39:32',NULL,'2026-04-12 16:39:30'),
(9,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:39:41','2026-04-12 16:39:43',NULL,'2026-04-12 16:39:41'),
(10,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Bibliotecario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Bibliotecario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:39:49','2026-04-12 16:39:52',NULL,'2026-04-12 16:39:49'),
(11,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:43:45','2026-04-12 16:44:28',NULL,'2026-04-12 16:43:45'),
(12,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:51:16','2026-04-12 16:52:06',NULL,'2026-04-12 16:50:07'),
(13,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Bibliotecario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Bibliotecario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:50:09','2026-04-12 16:51:59',NULL,'2026-04-12 16:50:09'),
(14,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:50:42','2026-04-12 16:52:02',NULL,'2026-04-12 16:50:42'),
(15,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',5,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 15:53:25','2026-04-12 16:54:18',NULL,'2026-04-12 16:53:25'),
(16,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:03:13','2026-04-12 17:05:08',NULL,'2026-04-12 17:03:13'),
(17,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Bibliotecario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Bibliotecario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:03:17','2026-04-12 17:05:08',NULL,'2026-04-12 17:03:17'),
(18,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:03:20','2026-04-12 17:05:08',NULL,'2026-04-12 17:03:20'),
(19,'info@softecsa.com','Admin','[TEST][HIGH] trigger worker',1,'<p>test high priority</p>','test high priority','sent',0,'2026-04-12 16:06:33','2026-04-12 17:06:36',NULL,'2026-04-12 17:06:33'),
(20,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>Carlos Hernández Ruiz d</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 17:49:37','2026-04-12 17:50:04',NULL,'2026-04-12 17:49:14'),
(21,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Bibliotecario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Bibliotecario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:56:45','2026-04-12 18:00:06',NULL,'2026-04-12 17:56:45'),
(22,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 16:57:10','2026-04-12 18:00:06',NULL,'2026-04-12 17:57:10'),
(23,'admin@biblioteca.local','Administrador del Sistema','[LOAN_REMINDER][#15] Recordatorio de vencimiento',5,'<p>Hola Administrador del Sistema,</p><p>Te recordamos que tu prestamo de <strong>Don Quijote de la Mancha</strong> vence el <strong>2026-04-13 15:48:02</strong>.</p><p>Si lo necesitas, realiza la renovacion antes del vencimiento.</p>','Hola Administrador del Sistema, tu prestamo de \"Don Quijote de la Mancha\" vence el 2026-04-13 15:48:02. Realiza la renovacion antes del vencimiento.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(24,'admin@biblioteca.local','Administrador del Sistema','[LOAN_REMINDER][#39] Recordatorio de vencimiento',5,'<p>Hola Administrador del Sistema,</p><p>Te recordamos que tu prestamo de <strong>Breve historia del tiempo</strong> vence el <strong>2026-04-13 15:48:02</strong>.</p><p>Si lo necesitas, realiza la renovacion antes del vencimiento.</p>','Hola Administrador del Sistema, tu prestamo de \"Breve historia del tiempo\" vence el 2026-04-13 15:48:02. Realiza la renovacion antes del vencimiento.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(25,'docente@biblioteca.local','María García López','[LOAN_REMINDER][#8] Recordatorio de vencimiento',5,'<p>Hola María García López,</p><p>Te recordamos que tu prestamo de <strong>Cien años de soledad</strong> vence el <strong>2026-04-13 15:48:02</strong>.</p><p>Si lo necesitas, realiza la renovacion antes del vencimiento.</p>','Hola María García López, tu prestamo de \"Cien años de soledad\" vence el 2026-04-13 15:48:02. Realiza la renovacion antes del vencimiento.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(26,'admin@biblioteca.local','Administrador del Sistema','[LOAN_OVERDUE][#7] Prestamo vencido',5,'<p>Hola Administrador del Sistema,</p><p>Tu prestamo de <strong>Cien años de soledad</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-12 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Administrador del Sistema, tu prestamo de \"Cien años de soledad\" ya esta vencido (fecha limite: 2026-04-12 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(27,'admin@biblioteca.local','Administrador del Sistema','[LOAN_OVERDUE][#31] Prestamo vencido',5,'<p>Hola Administrador del Sistema,</p><p>Tu prestamo de <strong>Sapiens: De animales a dioses</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-12 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Administrador del Sistema, tu prestamo de \"Sapiens: De animales a dioses\" ya esta vencido (fecha limite: 2026-04-12 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(28,'admin@biblioteca.local','Administrador del Sistema','[LOAN_OVERDUE][#51] Prestamo vencido',5,'<p>Hola Administrador del Sistema,</p><p>Tu prestamo de <strong>El señor de los anillos</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-12 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Administrador del Sistema, tu prestamo de \"El señor de los anillos\" ya esta vencido (fecha limite: 2026-04-12 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(29,'docente@biblioteca.local','María García López','[LOAN_OVERDUE][#26] Prestamo vencido',5,'<p>Hola María García López,</p><p>Tu prestamo de <strong>1985</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-11 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola María García López, tu prestamo de \"1985\" ya esta vencido (fecha limite: 2026-04-11 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(30,'docente@biblioteca.local','María García López','[LOAN_OVERDUE][#35] Prestamo vencido',5,'<p>Hola María García López,</p><p>Tu prestamo de <strong>El nombre de la rosa</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-10 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola María García López, tu prestamo de \"El nombre de la rosa\" ya esta vencido (fecha limite: 2026-04-10 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(31,'docente@biblioteca.local','María García López','[LOAN_OVERDUE][#45] Prestamo vencido',5,'<p>Hola María García López,</p><p>Tu prestamo de <strong>Orgullo y prejuicio</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-11 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola María García López, tu prestamo de \"Orgullo y prejuicio\" ya esta vencido (fecha limite: 2026-04-11 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(32,'docente@biblioteca.local','María García López','[LOAN_OVERDUE][#49] Prestamo vencido',5,'<p>Hola María García López,</p><p>Tu prestamo de <strong>Crimen y castigo</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-11 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola María García López, tu prestamo de \"Crimen y castigo\" ya esta vencido (fecha limite: 2026-04-11 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(33,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE][#6] Prestamo vencido',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Tu prestamo de <strong>Cien años de soledad</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-11 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Carlos Hernández Ruiz d, tu prestamo de \"Cien años de soledad\" ya esta vencido (fecha limite: 2026-04-11 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(34,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE][#14] Prestamo vencido',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Tu prestamo de <strong>Don Quijote de la Mancha</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-10 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Carlos Hernández Ruiz d, tu prestamo de \"Don Quijote de la Mancha\" ya esta vencido (fecha limite: 2026-04-10 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(35,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE][#21] Prestamo vencido',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Tu prestamo de <strong>El principito</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-12 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Carlos Hernández Ruiz d, tu prestamo de \"El principito\" ya esta vencido (fecha limite: 2026-04-12 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(36,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE][#47] Prestamo vencido',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Tu prestamo de <strong>El alquimista</strong> ya se encuentra vencido (fecha limite: <strong>2026-04-10 15:48:02</strong>).</p><p>Por favor realiza la devolucion lo antes posible para evitar acumulacion de multas.</p>','Hola Carlos Hernández Ruiz d, tu prestamo de \"El alquimista\" ya esta vencido (fecha limite: 2026-04-10 15:48:02). Devuelvelo lo antes posible.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(37,'docente@biblioteca.local','María García López','[LOAN_OVERDUE_SECOND][#26] Segunda notificacion de mora',5,'<p>Hola María García López,</p><p>Segunda notificacion: el prestamo de <strong>1985</strong> continua vencido desde <strong>2026-04-11 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola María García López, segunda notificacion: el prestamo de \"1985\" sigue vencido desde 2026-04-11 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(38,'docente@biblioteca.local','María García López','[LOAN_OVERDUE_SECOND][#35] Segunda notificacion de mora',5,'<p>Hola María García López,</p><p>Segunda notificacion: el prestamo de <strong>El nombre de la rosa</strong> continua vencido desde <strong>2026-04-10 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola María García López, segunda notificacion: el prestamo de \"El nombre de la rosa\" sigue vencido desde 2026-04-10 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(39,'docente@biblioteca.local','María García López','[LOAN_OVERDUE_SECOND][#45] Segunda notificacion de mora',5,'<p>Hola María García López,</p><p>Segunda notificacion: el prestamo de <strong>Orgullo y prejuicio</strong> continua vencido desde <strong>2026-04-11 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola María García López, segunda notificacion: el prestamo de \"Orgullo y prejuicio\" sigue vencido desde 2026-04-11 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(40,'docente@biblioteca.local','María García López','[LOAN_OVERDUE_SECOND][#49] Segunda notificacion de mora',5,'<p>Hola María García López,</p><p>Segunda notificacion: el prestamo de <strong>Crimen y castigo</strong> continua vencido desde <strong>2026-04-11 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola María García López, segunda notificacion: el prestamo de \"Crimen y castigo\" sigue vencido desde 2026-04-11 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(41,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE_SECOND][#6] Segunda notificacion de mora',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Segunda notificacion: el prestamo de <strong>Cien años de soledad</strong> continua vencido desde <strong>2026-04-11 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola Carlos Hernández Ruiz d, segunda notificacion: el prestamo de \"Cien años de soledad\" sigue vencido desde 2026-04-11 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(42,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE_SECOND][#14] Segunda notificacion de mora',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Segunda notificacion: el prestamo de <strong>Don Quijote de la Mancha</strong> continua vencido desde <strong>2026-04-10 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola Carlos Hernández Ruiz d, segunda notificacion: el prestamo de \"Don Quijote de la Mancha\" sigue vencido desde 2026-04-10 15:48:02.','sent',0,'2026-04-12 17:00:01','2026-04-12 18:02:19',NULL,'2026-04-12 18:00:01'),
(43,'estudiante1@biblioteca.local','Carlos Hernández Ruiz d','[LOAN_OVERDUE_SECOND][#47] Segunda notificacion de mora',5,'<p>Hola Carlos Hernández Ruiz d,</p><p>Segunda notificacion: el prestamo de <strong>El alquimista</strong> continua vencido desde <strong>2026-04-10 15:48:02</strong>.</p><p>Regulariza tu prestamo para evitar bloqueos o recargos adicionales.</p>','Hola Carlos Hernández Ruiz d, segunda notificacion: el prestamo de \"El alquimista\" sigue vencido desde 2026-04-10 15:48:02.','sent',0,'2026-04-12 18:02:26','2026-04-12 18:03:13',NULL,'2026-04-12 18:00:01'),
(44,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Usuario</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Usuario. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 20:33:27','2026-04-12 21:35:04',NULL,'2026-04-12 21:33:27'),
(45,'info@softecsa.com','CLAUDIO XAVIER BORJA SALTOS','Tu rol en Biblioteca fue actualizado',1,'<!doctype html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">\n  <title>Actualización de rol</title>\n  <style>\n    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }\n    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }\n    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }\n    table { border-collapse: collapse !important; }\n    body { margin: 0 !important; padding: 0 !important; width: 100% !important; }\n\n    @media screen and (max-width: 640px) {\n      .shell { width: 100% !important; }\n      .px-mobile { padding-left: 18px !important; padding-right: 18px !important; }\n      .title { font-size: 22px !important; }\n      .body-copy { font-size: 15px !important; }\n    }\n  </style>\n</head>\n<body style=\"margin:0;padding:0;background:#eef2ff;font-family:\'Trebuchet MS\',\'Segoe UI\',Roboto,Arial,sans-serif;color:#0f172a;\">\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">Cambio de rol en tu cuenta</div>\n  <div style=\"display:none;max-height:0;overflow:hidden;opacity:0;\">&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;</div>\n\n  <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#eef2ff;padding:26px 12px;\">\n    <tr>\n      <td align=\"center\">\n        <table class=\"shell\" role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;background:#ffffff;border:1px solid #dbe4ff;border-radius:18px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);\">\n          <tr>\n            <td class=\"px-mobile\" style=\"background:#1e3a8a;padding:20px 26px 16px;color:#ffffff;\">\n              <div style=\"font-size:11px;letter-spacing:.1em;text-transform:uppercase;opacity:.9;\">Sistema Biblioteca</div>\n              <div class=\"title\" style=\"font-size:28px;line-height:1.25;font-weight:700;margin-top:8px;\">Actualización de rol</div>\n              <div style=\"margin-top:10px;font-size:12px;opacity:.88;\">Notificacion automatica</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:24px 26px 8px;\">\n              <p class=\"body-copy\" style=\"margin:0 0 16px;font-size:16px;line-height:1.6;color:#334155;\">Se aplicó un cambio en los permisos de tu cuenta.</p>\n\n              <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\" style=\"background:#f8faff;border:1px solid #dbe4ff;border-radius:12px;\">\n                <tr>\n                  <td style=\"padding:16px 16px 14px;font-size:15px;line-height:1.68;color:#0f172a;\">\n                    <p>Hola <strong>CLAUDIO XAVIER BORJA SALTOS</strong>,</p><p>Tu rol en la plataforma fue actualizado por <strong>Administrador del Sistema</strong>.</p><p><strong>Nuevo rol:</strong> Docente</p><p>Si no reconoces este cambio, contacta al administrador de inmediato.</p>\n                  </td>\n                </tr>\n              </table>\n\n              <div style=\"height:14px;line-height:14px;font-size:14px;\">&nbsp;</div>\n            </td>\n          </tr>\n\n          <tr>\n            <td class=\"px-mobile\" style=\"padding:16px 26px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:12px;line-height:1.65;color:#64748b;\">\n              <div style=\"font-weight:700;color:#334155;\">Biblioteca - Mensajeria automatica</div>\n              <div>Este correo fue generado por el sistema. 2026</div>\n              <div>Notificación automática del sistema de biblioteca.</div>\n            </td>\n          </tr>\n        </table>\n\n        <table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"640\" style=\"width:100%;max-width:640px;\">\n          <tr>\n            <td style=\"padding:12px 6px 0;text-align:center;font-size:11px;line-height:1.6;color:#94a3b8;\">\n              Si no reconoces este mensaje, ignoralo o contacta al administrador.\n            </td>\n          </tr>\n        </table>\n      </td>\n    </tr>\n  </table>\n</body>\n</html>','Actualización de rol\n====================\n\nSe aplicó un cambio en los permisos de tu cuenta.\n\nTu nuevo rol es: Docente. Si no reconoces este cambio, contacta al administrador.\n\nBiblioteca - Mensajeria automatica\nNotificación automática del sistema de biblioteca.','sent',0,'2026-04-12 20:51:30','2026-04-12 21:55:04',NULL,'2026-04-12 21:51:30');
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verifications`
--

DROP TABLE IF EXISTS `email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_verifications_user` (`user_id`),
  UNIQUE KEY `uq_email_verifications_token` (`token_hash`),
  KEY `idx_email_verifications_expires` (`expires_at`),
  CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verifications`
--

LOCK TABLES `email_verifications` WRITE;
/*!40000 ALTER TABLE `email_verifications` DISABLE KEYS */;
INSERT INTO `email_verifications` VALUES
(3,192,'93c170da809a886aa75d47598dff8dc3475df55f8ffa9ffdbdcdfb62b19fa15a','2026-04-13 13:31:24','2026-04-12 14:32:16','2026-04-12 14:31:24');
/*!40000 ALTER TABLE `email_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fines`
--

DROP TABLE IF EXISTS `fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `hours_overdue` int(11) NOT NULL DEFAULT 0,
  `replacement_cost_at_fine` decimal(8,2) NOT NULL,
  `reason` enum('overdue','damage','loss') NOT NULL DEFAULT 'overdue',
  `status` enum('pending','partially_paid','paid','waived') NOT NULL DEFAULT 'pending',
  `amount_paid` decimal(8,2) NOT NULL DEFAULT 0.00,
  `waiver_reason` text DEFAULT NULL,
  `waived_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_fines_waived_by` (`waived_by`),
  KEY `idx_fines_user_status` (`user_id`,`status`),
  KEY `idx_fines_loan` (`loan_id`),
  CONSTRAINT `fk_fines_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fines_waived_by` FOREIGN KEY (`waived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fines`
--

LOCK TABLES `fines` WRITE;
/*!40000 ALTER TABLE `fines` DISABLE KEYS */;
INSERT INTO `fines` VALUES
(1,54,1,240.00,0,240.00,'loss','waived',0.00,'Condonacion administrativa',1,'2026-04-12 20:40:40','2026-04-12 20:42:07');
/*!40000 ALTER TABLE `fines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_branches`
--

DROP TABLE IF EXISTS `library_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `schedule` text DEFAULT NULL,
  `manager_id` int(10) unsigned DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`code`),
  UNIQUE KEY `uq_branches_name` (`name`),
  KEY `fk_branches_manager` (`manager_id`),
  CONSTRAINT `fk_branches_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_branches`
--

LOCK TABLES `library_branches` WRITE;
/*!40000 ALTER TABLE `library_branches` DISABLE KEYS */;
INSERT INTO `library_branches` VALUES
(1,'XXC','SEDE CENTRAL','7 DE MAYO, OLMEDO','099655555','info@huellasdepapel.online','DDDEEDED',NULL,0,'active',10,'2026-04-11 23:07:37','2026-04-11 23:07:37');
/*!40000 ALTER TABLE `library_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `librarian_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `loan_at` datetime NOT NULL DEFAULT current_timestamp(),
  `due_at` datetime NOT NULL,
  `returned_at` datetime DEFAULT NULL,
  `loan_hours_applied` smallint(6) NOT NULL DEFAULT 72,
  `renewals_count` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('active','returned','overdue','lost') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_loans_librarian` (`librarian_id`),
  KEY `idx_loans_user_status` (`user_id`,`status`),
  KEY `idx_loans_due_status` (`due_at`,`status`),
  KEY `idx_loans_branch_status` (`branch_id`,`status`),
  KEY `idx_loans_resource_status` (`resource_id`,`status`),
  CONSTRAINT `fk_loans_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_librarian` FOREIGN KEY (`librarian_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_loans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES
(53,51,1,1,1,'2026-04-12 20:13:16','2026-04-16 20:13:16','2026-04-12 20:36:16',72,1,'returned',NULL,'2026-04-12 20:13:16'),
(54,7,1,1,NULL,'2026-04-12 20:13:17','2026-04-15 20:13:17',NULL,72,0,'lost',NULL,'2026-04-12 20:13:17'),
(55,14,1,1,NULL,'2026-04-12 20:13:18','2026-04-18 20:13:18','2026-04-12 20:33:56',72,1,'returned',NULL,'2026-04-12 20:13:18');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_migration` (`migration`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'001_create_categories.sql','2026-04-11 14:32:55'),
(2,'002_create_library_branches.sql','2026-04-11 14:32:55'),
(3,'003_create_users.sql','2026-04-11 14:32:55'),
(4,'004_create_books.sql','2026-04-11 14:32:55'),
(5,'005_create_loans.sql','2026-04-11 14:32:56'),
(6,'006_create_reservations.sql','2026-04-11 14:32:56'),
(7,'007_create_fines.sql','2026-04-11 14:32:56'),
(8,'008_create_email_queue.sql','2026-04-11 14:32:56'),
(9,'009_create_audit_logs.sql','2026-04-11 14:32:56'),
(10,'010_create_system_settings.sql','2026-04-11 14:32:56'),
(11,'011_create_password_resets.sql','2026-04-11 14:35:40'),
(12,'012_create_search_log.sql','2026-04-11 14:35:40'),
(13,'013_create_news.sql','2026-04-11 14:35:40'),
(14,'014_create_visits_log.sql','2026-04-11 14:35:40'),
(15,'015_create_teacher_groups.sql','2026-04-11 14:35:40'),
(16,'016_create_teacher_group_students.sql','2026-04-11 14:35:40'),
(17,'017_create_reading_assignments.sql','2026-04-11 14:35:40'),
(18,'018_create_reading_assignment_students.sql','2026-04-11 14:35:40'),
(19,'019_create_book_suggestions.sql','2026-04-11 14:35:40'),
(20,'020_create_digital_access_log.sql','2026-04-11 14:35:40'),
(21,'021_add_marc21_to_books.sql','2026-04-11 21:07:48'),
(22,'022_backfill_marc21_books.sql','2026-04-11 21:12:47'),
(23,'023_rebuild_marc21_existing_books.sql','2026-04-11 21:16:08'),
(24,'024_add_rda_resource_fields.sql','2026-04-11 21:47:51'),
(25,'004_create_resources.sql','2026-04-12 08:14:00'),
(26,'019_create_resource_suggestions.sql','2026-04-12 08:14:00'),
(27,'030_add_priority_to_email_queue.sql','2026-04-12 17:01:12');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` text NOT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_published` (`is_published`,`published_at`),
  KEY `idx_news_author` (`author_id`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES
(1,'Inauguramos nuestro nuevo espacio de lectura infantil','nuevo-espacio-lectura-infantil','La biblioteca inaugura una sala especialmente diseñada para los más pequeños, con mobiliario ergonómico, iluminación cálida y más de 300 títulos de literatura infantil.','<p>Con gran alegría anunciamos la apertura de nuestro nuevo <strong>Rincón de los Lectores</strong>, un espacio diseñado pensando en los niños y niñas de nuestra comunidad. La sala cuenta con sillas y mesas adaptadas a distintas edades, cojines de lectura, murales ilustrados y una selección curada de más de 300 títulos entre álbumes ilustrados, cuentos clásicos y libros STEM para primaria.</p><p>El horario de la sala infantil es de lunes a viernes de 9:00 a 17:00 y los sábados de 10:00 a 14:00. Los talleres de cuentacuentos se realizarán cada sábado a las 11:00 con inscripción previa gratuita.</p><p>Para más información puedes consultar en el mostrador de atención al público o escribirnos a través del formulario de contacto.</p>','/biblioteca/public/uploads/news/nuevo-espacio-lectura-infantil.jpg',1,'2026-04-08 09:00:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(2,'Ampliamos nuestra colección digital: más de 200 nuevos e-books','ampliacion-coleccion-digital','Incorporamos 200 títulos en formato digital que ya están disponibles para todos los socios. Literatura contemporánea, ciencia y ensayo son las categorías más reforzadas.','<p>Gracias a la renovación del convenio con nuestros proveedores de contenido digital, la biblioteca suma <strong>200 nuevos e-books</strong> a su catálogo en línea. Los usuarios con carnet activo pueden acceder desde cualquier dispositivo a través de la sección <em>Libros Digitales</em> del catálogo.</p><p>Entre las novedades destacan colecciones completas de autores latinoamericanos contemporáneos, manuales universitarios actualizados y una amplia selección de ensayo científico en español e inglés.</p><p>Los libros digitales no tienen límite de copias ni fecha de devolución, por lo que están disponibles de forma simultánea para todos los socios. Accede desde el catálogo y filtra por <em>Tipo: Digital</em>.</p>','/biblioteca/public/uploads/news/ampliacion-coleccion-digital.jpg',1,'2026-04-05 10:30:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(3,'Taller de escritura creativa para adultos — Inscripciones abiertas','taller-escritura-creativa-adultos','Abrimos inscripciones para el taller de escritura creativa de primavera, dirigido a adultos sin experiencia previa. Cupo limitado a 15 participantes.','<p>Este <strong>primavera</strong> arranca una nueva edición del taller de escritura creativa para adultos, conducido por la autora local Marta Villanueva. El taller tiene una duración de ocho semanas, con sesiones de dos horas cada martes a las 18:30.</p><p>No se requiere experiencia previa. Solo hace falta ganas de escribir y una libreta. El cupo es de <strong>15 participantes</strong> para garantizar una atención personalizada.</p><h3>Programa:</h3><ul><li>Semanas 1-2: El punto de vista narrativo</li><li>Semanas 3-4: Construcción de personajes</li><li>Semanas 5-6: Diálogo y voz</li><li>Semanas 7-8: Revisión y taller colectivo</li></ul><p>La inscripción es gratuita para socios activos y tiene un coste de 20€ para no socios. Plazas disponibles hasta completar el aforo. Inscríbete en el mostrador o por correo electrónico.</p>','/biblioteca/public/uploads/news/taller-escritura-creativa-adultos.jpg',1,'2026-04-01 08:00:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(4,'Semana del libro 2026: programa completo de actividades','semana-libro-2026','Del 20 al 26 de abril celebramos la Semana del libro con presentaciones, clubs de lectura, charlas de autores y un mercadillo solidario. Todos los eventos son gratuitos.','<p>La <strong>Semana del Libro 2026</strong> llega cargada de actividades para todos los públicos. Durante siete días la biblioteca se transforma en un espacio de encuentro, debate y celebración de la lectura.</p><h3>Programación destacada</h3><ul><li><strong>Lunes 20</strong> — Apertura oficial y presentación del libro <em>Mapas del silencio</em> de Rodrigo Fuentes (18:00).</li><li><strong>Martes 21</strong> — Mesa redonda: «La traducción literaria, el arte invisible» (17:30).</li><li><strong>Miércoles 22</strong> — Día del Libro: intercambio de libros en el patio y descuentos en socios nuevos.</li><li><strong>Jueves 23</strong> — Maratón de cuentacuentos para familias (10:00 - 14:00).</li><li><strong>Viernes 24</strong> — Charla: «Leer en tiempos digitales» con investigadores de comunicación (19:00).</li><li><strong>Sábado 25</strong> — Mercadillo solidario de libros usados; todos los fondos van a la Biblioteca Rural.</li><li><strong>Domingo 26</strong> — Club de lectura abierto: participa y comparte tu última lectura (11:00).</li></ul><p>Todos los eventos son gratuitos y de acceso libre hasta completar el aforo de cada sala.</p>','/biblioteca/public/uploads/news/semana-libro-2026.jpg',1,'2026-03-25 09:00:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(5,'Nuevo horario de verano a partir del 1 de mayo','nuevo-horario-verano','A partir del 1 de mayo la biblioteca amplía su horario de apertura hasta las 21:00 de lunes a jueves para dar servicio a estudiantes y trabajadores.','<p>Con el objetivo de mejorar la accesibilidad para estudiantes y personas que trabajan durante el día, la biblioteca <strong>amplía su horario a partir del 1 de mayo</strong>.</p><p>El nuevo horario de verano será:</p><ul><li><strong>Lunes a Jueves:</strong> 8:30 - 21:00</li><li><strong>Viernes:</strong> 8:30 - 18:00</li><li><strong>Sábados:</strong> 10:00 - 14:00</li><li><strong>Domingos y festivos:</strong> Cerrado</li></ul><p>La sala de estudio en silencio permanecerá abierta hasta las 21:00. El servicio de préstamo y devolución cerrará a las 20:30. Los cambios entrarán en vigor el próximo <strong>viernes 1 de mayo</strong> y se mantendrán hasta el 31 de agosto.</p>','/biblioteca/public/uploads/news/nuevo-horario-verano.jpg',1,'2026-03-20 11:00:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(6,'Club de lectura: nueva temporada con 12 obras seleccionadas','club-lectura-nueva-temporada','El club de lectura mensual arranca nueva temporada con una selección de 12 obras que mezcla narrativa contemporánea, ensayo y un clásico rescatado.','<p>El <strong>Club de Lectura de la Biblioteca</strong> retoma sus reuniones mensuales con una programación renovada para el segundo trimestre. Las sesiones tienen lugar el último jueves de cada mes a las 18:30 en la sala polivalente.</p><p>La selección para esta temporada ha sido votada por los propios socios a través del formulario de participación:</p><ol><li>Abril — <em>La amiga estupenda</em>, Elena Ferrante</li><li>Mayo — <em>El año del pensamiento mágico</em>, Joan Didion</li><li>Junio — <em>El infinito en un junco</em>, Irene Vallejo</li><li>Julio — <em>Americanah</em>, Chimamanda Ngozi Adichie</li></ol><p>La participación es libre y gratuita para todos los socios. Solo es necesario haber leído el libro del mes. Puedes reservar tu ejemplar en el mostrador con dos semanas de antelación indicando \"Club de lectura\".</p>','/biblioteca/public/uploads/news/club-lectura-nueva-temporada.jpg',1,'2026-03-15 08:00:00',1,'2026-04-11 16:13:21','2026-04-11 16:17:46'),
(7,'19852626','19852626','2626+62','<p><strong>120651</strong></p><p><strong><em><u>3262</u></em></strong></p>','/uploads/news/news_20260412_200743_f86a84dd.jpg',1,'2026-04-08 14:45:00',1,'2026-04-12 21:07:43','2026-04-12 21:07:56');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_token` (`token_hash`),
  KEY `idx_password_resets_user` (`user_id`),
  KEY `idx_password_resets_expires` (`expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reading_assignment_students`
--

DROP TABLE IF EXISTS `reading_assignment_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_assignment_students` (
  `assignment_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_id`,`student_id`),
  KEY `idx_ras_student` (`student_id`),
  KEY `idx_ras_status` (`status`),
  CONSTRAINT `fk_ras_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `reading_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ras_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_assignment_students`
--

LOCK TABLES `reading_assignment_students` WRITE;
/*!40000 ALTER TABLE `reading_assignment_students` DISABLE KEYS */;
INSERT INTO `reading_assignment_students` VALUES
(1,3,'in_progress',NULL,NULL,'2026-04-11 14:39:04');
/*!40000 ALTER TABLE `reading_assignment_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reading_assignments`
--

DROP TABLE IF EXISTS `reading_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `resource_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reading_assignments_group` (`group_id`),
  KEY `idx_reading_assignments_due` (`due_date`),
  KEY `idx_reading_assignments_resource` (`resource_id`),
  CONSTRAINT `fk_reading_assignments_group` FOREIGN KEY (`group_id`) REFERENCES `teacher_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reading_assignments_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reading_assignments`
--

LOCK TABLES `reading_assignments` WRITE;
/*!40000 ALTER TABLE `reading_assignments` DISABLE KEYS */;
INSERT INTO `reading_assignments` VALUES
(1,1,1,'Lectura: Cien años de soledad','Leer los primeros 10 capítulos y preparar un resumen de los personajes principales.','2026-05-11',1,'2026-04-11 14:39:04','2026-04-11 14:39:04');
/*!40000 ALTER TABLE `reading_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `queue_position` int(10) unsigned NOT NULL,
  `status` enum('waiting','notified','fulfilled','cancelled','expired') NOT NULL DEFAULT 'waiting',
  `notified_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reservations_user` (`user_id`,`status`),
  KEY `idx_reservations_resource_queue` (`resource_id`,`queue_position`,`status`),
  CONSTRAINT `fk_reservations_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES
(1,59,1,1,'fulfilled','2026-04-12 19:37:52',NULL,'2026-04-12 19:37:43','2026-04-12 19:37:52'),
(2,14,1,1,'fulfilled','2026-04-12 20:13:18',NULL,'2026-04-12 19:38:58','2026-04-12 20:13:18'),
(3,7,1,1,'fulfilled','2026-04-12 20:13:17',NULL,'2026-04-12 20:12:57','2026-04-12 20:13:17'),
(4,51,1,1,'fulfilled','2026-04-12 20:13:16',NULL,'2026-04-12 20:13:09','2026-04-12 20:13:16'),
(5,1,192,1,'waiting',NULL,NULL,'2026-04-12 21:50:57','2026-04-12 21:50:57'),
(6,52,192,1,'waiting',NULL,NULL,'2026-04-12 21:51:10','2026-04-12 21:51:10');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource_suggestions`
--

DROP TABLE IF EXISTS `resource_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_suggestions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(17) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','acquired') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_book_suggestions_reviewer` (`reviewed_by`),
  KEY `idx_resource_suggestions_user` (`user_id`),
  KEY `idx_resource_suggestions_status` (`status`),
  CONSTRAINT `fk_resource_suggestions_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_resource_suggestions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_suggestions`
--

LOCK TABLES `resource_suggestions` WRITE;
/*!40000 ALTER TABLE `resource_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `resource_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isbn_13` char(13) DEFAULT NULL,
  `marc_leader` char(24) DEFAULT NULL,
  `marc_control_number` varchar(64) DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `authors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`authors`)),
  `marc_record` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`marc_record`)),
  `publisher` varchar(200) DEFAULT NULL,
  `edition_statement` varchar(200) DEFAULT NULL,
  `publication_year` smallint(6) DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `support_type` enum('physical','digital','audiovisual','journal','thesis','map','score','kit','game','other') NOT NULL DEFAULT 'physical',
  `resource_type` varchar(60) DEFAULT NULL,
  `content_type` varchar(80) DEFAULT NULL,
  `media_type` varchar(80) DEFAULT NULL,
  `carrier_type` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pages` smallint(5) unsigned DEFAULT NULL,
  `language` char(2) NOT NULL DEFAULT 'es',
  `cover_image` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `digital_url` text DEFAULT NULL,
  `acquisition_price` decimal(8,2) DEFAULT NULL,
  `replacement_cost` decimal(8,2) NOT NULL,
  `acquisition_date` date DEFAULT NULL,
  `acquired_at` datetime DEFAULT NULL,
  `is_new_acquisition` tinyint(1) NOT NULL DEFAULT 1,
  `total_copies` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `available_copies` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `digital_access_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deactivated_at` datetime DEFAULT NULL,
  `deactivated_by` int(10) unsigned DEFAULT NULL,
  `deactivation_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_books_marc_control_number` (`marc_control_number`),
  UNIQUE KEY `uq_resources_isbn` (`isbn_13`),
  KEY `idx_resources_support_active` (`support_type`,`is_active`),
  KEY `idx_resources_branch` (`branch_id`,`is_active`),
  KEY `idx_resources_new_acquisition` (`is_new_acquisition`,`acquired_at`),
  KEY `idx_resources_category` (`category_id`),
  KEY `fk_resources_deactivated_by` (`deactivated_by`),
  FULLTEXT KEY `ft_books_search` (`title`,`publisher`),
  CONSTRAINT `fk_resources_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resources_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_resources_deactivated_by` FOREIGN KEY (`deactivated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
INSERT INTO `resources` VALUES
(1,'9780060531041','00000nam a2200000 i 4500','BIB-00000001','Cien años de soledad','[\"Gabriel García Márquez\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000001\", \"020\": \"9780060531041\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Gabriel García Márquez\"}, \"245\": {\"a\": \"Cien años de soledad\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Harper Perennial\", \"c\": \"1967\"}, \"300\": {\"a\": \"422 p.\"}, \"520\": {\"a\": \"La historia de la familia Buendía a lo largo de siete generaciones en el mítico pueblo de Macondo. Obra cumbre del realismo mágico y de la literatura latinoamericana.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Gabriel García Márquez\"]}, \"856\": {\"u\": \"\"}}}','Harper Perennial',NULL,1967,1,NULL,'physical',NULL,NULL,NULL,NULL,'La historia de la familia Buendía a lo largo de siete generaciones en el mítico pueblo de Macondo. Obra cumbre del realismo mágico y de la literatura latinoamericana.',422,'es','/biblioteca/public/uploads/covers/cien-anos-de-soledad.jpg','Estante A-1',NULL,250.00,300.00,'2024-01-15','2024-01-15 10:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-11 14:39:04','2026-04-11 21:16:08'),
(2,'9780060934347','00000nam a2200000 i 4500','BIB-00000002','Don Quijote de la Mancha','[\"Miguel de Cervantes Saavedra\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000002\", \"020\": \"9780060934347\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Miguel de Cervantes Saavedra\"}, \"245\": {\"a\": \"Don Quijote de la Mancha\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Alfaguara\", \"c\": \"1605\"}, \"300\": {\"a\": \"1100 p.\"}, \"520\": {\"a\": \"La novela más influyente de la literatura española y una de las obras más importantes de la literatura universal. Las aventuras del ingenioso hidalgo don Quijote y su leal escudero Sancho Panza.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Miguel de Cervantes Saavedra\"]}, \"856\": {\"u\": \"\"}}}','Alfaguara',NULL,1605,1,NULL,'physical',NULL,NULL,NULL,NULL,'La novela más influyente de la literatura española y una de las obras más importantes de la literatura universal. Las aventuras del ingenioso hidalgo don Quijote y su leal escudero Sancho Panza.',1100,'es','/biblioteca/public/uploads/covers/don-quijote.jpg','Estante A-1',NULL,180.00,220.00,'2024-01-20','2024-01-20 09:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(3,'9780156012195','00000nam a2200000 i 4500','BIB-00000003','El principito','[\"Antoine de Saint-Exupéry\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000003\", \"020\": \"9780156012195\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Antoine de Saint-Exupéry\"}, \"245\": {\"a\": \"El principito\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Harcourt\", \"c\": \"1943\"}, \"300\": {\"a\": \"96 p.\"}, \"520\": {\"a\": \"El clásico cuento de un piloto que conoce a un pequeño príncipe que ha viajado de planeta en planeta. Una obra atemporal sobre la amistad, el amor y los valores esenciales de la vida.\"}, \"650\": {\"a\": [\"Infantil y Juvenil\"]}, \"700\": {\"a\": [\"Antoine de Saint-Exupéry\"]}, \"856\": {\"u\": \"\"}}}','Harcourt',NULL,1943,18,NULL,'physical',NULL,NULL,NULL,NULL,'El clásico cuento de un piloto que conoce a un pequeño príncipe que ha viajado de planeta en planeta. Una obra atemporal sobre la amistad, el amor y los valores esenciales de la vida.',96,'es','/biblioteca/public/uploads/covers/el-principito.jpg','Estante F-3',NULL,120.00,150.00,'2024-02-01','2024-02-01 10:00:00',1,4,4,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(4,'9780451524935','00000nam a2200000 i 4500','BIB-00000004','1985','[\"George Orwell\",\"George Orwellsss\"]','{\"leader\":\"00000nam a2200000 i 4500\",\"controlfields\":{\"001\":\"\",\"020\":\"9780451524935\",\"041\":\"es\"},\"datafields\":{\"100\":{\"a\":\"George Orwell\"},\"245\":{\"a\":\"1985\",\"b\":\"\"},\"250\":{\"a\":\"s\"},\"260\":{\"b\":\"Signet Classics\",\"c\":\"1949\"},\"300\":{\"a\":\"\"},\"520\":{\"a\":\"En un futuro distópico bajo el control del Gran Hermano, Winston Smith trabaja para el Partido reescribiendo la historia. Una novela profética sobre la vigilancia, la propaganda y la resistencia.\"},\"650\":{\"a\":[]},\"700\":{\"a\":[]},\"856\":{\"u\":\"\"}},\"rda\":{\"resource_type\":\"book\",\"content_type\":\"\",\"media_type\":\"\",\"carrier_type\":\"\"}}','Signet Classics','s',1949,1,1,'physical','book',NULL,NULL,NULL,'En un futuro distópico bajo el control del Gran Hermano, Winston Smith trabaja para el Partido reescribiendo la historia. Una novela profética sobre la vigilancia, la propaganda y la resistencia.',328,'es','/biblioteca/public/uploads/covers/1984.jpg','Estante A-2',NULL,150.00,180.00,'2024-02-10','2024-02-10 11:00:00',1,4,3,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-12 17:44:47'),
(5,'9780062316097','00000nam a2200000 i 4500','BIB-00000005','Sapiens: De animales a dioses','[\"Yuval Noah Harari\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000005\", \"020\": \"9780062316097\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Yuval Noah Harari\"}, \"245\": {\"a\": \"Sapiens: De animales a dioses\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Harper Collins\", \"c\": \"2011\"}, \"300\": {\"a\": \"464 p.\"}, \"520\": {\"a\": \"Una breve historia de la humanidad que recorre 70.000 años de existencia humana, desde la aparición del Homo sapiens hasta la actualidad, analizando cómo llegamos a dominar la Tierra.\"}, \"650\": {\"a\": [\"Historia\"]}, \"700\": {\"a\": [\"Yuval Noah Harari\"]}, \"856\": {\"u\": \"\"}}}','Harper Collins',NULL,2011,4,NULL,'physical',NULL,NULL,NULL,NULL,'Una breve historia de la humanidad que recorre 70.000 años de existencia humana, desde la aparición del Homo sapiens hasta la actualidad, analizando cómo llegamos a dominar la Tierra.',464,'es','/biblioteca/public/uploads/covers/sapiens.jpg','Estante D-2',NULL,280.00,320.00,'2024-03-01','2024-03-01 09:30:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(6,'9780156001311','00000nam a2200000 i 4500','BIB-00000006','El nombre de la rosa','[\"Umberto Eco\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000006\", \"020\": \"9780156001311\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Umberto Eco\"}, \"245\": {\"a\": \"El nombre de la rosa\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Harcourt\", \"c\": \"1980\"}, \"300\": {\"a\": \"502 p.\"}, \"520\": {\"a\": \"Un monje franciscano y su novicio investigan una serie de muertes misteriosas en una abadía medieval italiana. Una fascinante combinación de misterio medieval, semiología y filosofía.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Umberto Eco\"]}, \"856\": {\"u\": \"\"}}}','Harcourt',NULL,1980,1,NULL,'physical',NULL,NULL,NULL,NULL,'Un monje franciscano y su novicio investigan una serie de muertes misteriosas en una abadía medieval italiana. Una fascinante combinación de misterio medieval, semiología y filosofía.',502,'es','/biblioteca/public/uploads/covers/el-nombre-de-la-rosa.jpg','Estante A-3',NULL,220.00,260.00,'2024-03-05','2024-03-05 10:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(7,'9780553380163','00000nam a2200000 i 4500','BIB-00000007','Breve historia del tiempo','[\"Stephen Hawking\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000007\", \"020\": \"9780553380163\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Stephen Hawking\"}, \"245\": {\"a\": \"Breve historia del tiempo\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Bantam Books\", \"c\": \"1988\"}, \"300\": {\"a\": \"212 p.\"}, \"520\": {\"a\": \"Desde el Big Bang hasta los agujeros negros, Hawking explica los temas más complejos de la física y la cosmología de forma accesible para el lector general.\"}, \"650\": {\"a\": [\"Ciencias Naturales\"]}, \"700\": {\"a\": [\"Stephen Hawking\"]}, \"856\": {\"u\": \"\"}}}','Bantam Books',NULL,1988,2,NULL,'physical',NULL,NULL,NULL,NULL,'Desde el Big Bang hasta los agujeros negros, Hawking explica los temas más complejos de la física y la cosmología de forma accesible para el lector general.',212,'es','/biblioteca/public/uploads/covers/breve-historia-tiempo.jpg','Estante B-1',NULL,200.00,240.00,'2024-03-10','2024-03-10 11:00:00',1,2,1,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-12 20:13:17'),
(8,'9781557427663','00000nam a2200000 i 4500','BIB-00000008','La metamorfosis','[\"Franz Kafka\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000008\", \"020\": \"9781557427663\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Franz Kafka\"}, \"245\": {\"a\": \"La metamorfosis\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Bantam Classics\", \"c\": \"1915\"}, \"300\": {\"a\": \"96 p.\"}, \"520\": {\"a\": \"Gregor Samsa despierta una mañana convertido en un enorme insecto. Una de las obras más influyentes de la literatura universal, explora los temas del aislamiento, la culpa y la alienación.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Franz Kafka\"]}, \"856\": {\"u\": \"\"}}}','Bantam Classics',NULL,1915,1,NULL,'physical',NULL,NULL,NULL,NULL,'Gregor Samsa despierta una mañana convertido en un enorme insecto. Una de las obras más influyentes de la literatura universal, explora los temas del aislamiento, la culpa y la alienación.',96,'es','/biblioteca/public/uploads/covers/la-metamorfosis.jpg','Estante A-4',NULL,110.00,140.00,'2024-03-15','2024-03-15 09:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(9,'9780141439518','00000nam a2200000 i 4500','BIB-00000009','Orgullo y prejuicio','[\"Jane Austen\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000009\", \"020\": \"9780141439518\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Jane Austen\"}, \"245\": {\"a\": \"Orgullo y prejuicio\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Penguin Classics\", \"c\": \"1813\"}, \"300\": {\"a\": \"432 p.\"}, \"520\": {\"a\": \"La historia de amor entre Elizabeth Bennet y el orgulloso señor Darcy. Una exploración brillante de la sociedad inglesa del siglo XIX y uno de los romances más célebres de la literatura.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Jane Austen\"]}, \"856\": {\"u\": \"\"}}}','Penguin Classics',NULL,1813,1,NULL,'physical',NULL,NULL,NULL,NULL,'La historia de amor entre Elizabeth Bennet y el orgulloso señor Darcy. Una exploración brillante de la sociedad inglesa del siglo XIX y uno de los romances más célebres de la literatura.',432,'es','/biblioteca/public/uploads/covers/orgullo-y-prejuicio.jpg','Estante A-5',NULL,150.00,180.00,'2024-04-01','2024-04-01 10:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(10,'9780062502179','00000nam a2200000 i 4500','BIB-00000010','El alquimista','[\"Paulo Coelho\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000010\", \"020\": \"9780062502179\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Paulo Coelho\"}, \"245\": {\"a\": \"El alquimista\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Harper One\", \"c\": \"1988\"}, \"300\": {\"a\": \"208 p.\"}, \"520\": {\"a\": \"La historia de Santiago, un joven pastor andaluz que sueña con encontrar un tesoro junto a las pirámides de Egipto. Un relato sobre seguir los sueños y escuchar el corazón.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Paulo Coelho\"]}, \"856\": {\"u\": \"\"}}}','Harper One',NULL,1988,1,NULL,'physical',NULL,NULL,NULL,NULL,'La historia de Santiago, un joven pastor andaluz que sueña con encontrar un tesoro junto a las pirámides de Egipto. Un relato sobre seguir los sueños y escuchar el corazón.',208,'es','/biblioteca/public/uploads/covers/el-alquimista.jpg','Estante A-6',NULL,160.00,200.00,'2024-04-05','2024-04-05 10:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(11,'9780140449136','00000nam a2200000 i 4500','BIB-00000011','Crimen y castigo','[\"Fiódor Dostoievski\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000011\", \"020\": \"9780140449136\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Fiódor Dostoievski\"}, \"245\": {\"a\": \"Crimen y castigo\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Penguin Classics\", \"c\": \"1866\"}, \"300\": {\"a\": \"576 p.\"}, \"520\": {\"a\": \"La novela sigue a Raskolnikov, un estudiante que comete un asesinato y lucha con la culpa y el arrepentimiento. Una profunda exploración psicológica de la naturaleza del crimen y la redención.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Fiódor Dostoievski\"]}, \"856\": {\"u\": \"\"}}}','Penguin Classics',NULL,1866,1,NULL,'physical',NULL,NULL,NULL,NULL,'La novela sigue a Raskolnikov, un estudiante que comete un asesinato y lucha con la culpa y el arrepentimiento. Una profunda exploración psicológica de la naturaleza del crimen y la redención.',576,'es','/biblioteca/public/uploads/covers/crimen-y-castigo.jpg','Estante A-7',NULL,180.00,220.00,'2024-04-10','2024-04-10 11:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(12,'9780618640157','00000nam a2200000 i 4500','BIB-00000012','El señor de los anillos','[\"J.R.R. Tolkien\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000012\", \"020\": \"9780618640157\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"J.R.R. Tolkien\"}, \"245\": {\"a\": \"El señor de los anillos\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Houghton Mifflin\", \"c\": \"1954\"}, \"300\": {\"a\": \"1216 p.\"}, \"520\": {\"a\": \"La épica historia de la Comunidad del Anillo y su misión de destruir el Anillo Único para salvar la Tierra Media del poder del señor oscuro Sauron. La obra fundacional de la fantasía moderna.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"J.R.R. Tolkien\"]}, \"856\": {\"u\": \"\"}}}','Houghton Mifflin',NULL,1954,1,NULL,'physical',NULL,NULL,NULL,NULL,'La épica historia de la Comunidad del Anillo y su misión de destruir el Anillo Único para salvar la Tierra Media del poder del señor oscuro Sauron. La obra fundacional de la fantasía moderna.',1216,'es','/biblioteca/public/uploads/covers/el-senor-de-los-anillos.jpg','Estante A-8',NULL,320.00,380.00,'2024-04-15','2024-04-15 09:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(13,'9780439023481','00000nam a2200000 i 4500','BIB-00000013','Los juegos del hambre','[\"Suzanne Collins\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000013\", \"020\": \"9780439023481\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Suzanne Collins\"}, \"245\": {\"a\": \"Los juegos del hambre\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Scholastic\", \"c\": \"2008\"}, \"300\": {\"a\": \"374 p.\"}, \"520\": {\"a\": \"En la distópica nación de Panem, Katniss Everdeen se ofrece voluntaria para participar en Los Juegos del Hambre en lugar de su hermana menor. Una historia de supervivencia, sacrificio y rebeldía.\"}, \"650\": {\"a\": [\"Literatura\"]}, \"700\": {\"a\": [\"Suzanne Collins\"]}, \"856\": {\"u\": \"\"}}}','Scholastic',NULL,2008,1,NULL,'physical',NULL,NULL,NULL,NULL,'En la distópica nación de Panem, Katniss Everdeen se ofrece voluntaria para participar en Los Juegos del Hambre en lugar de su hermana menor. Una historia de supervivencia, sacrificio y rebeldía.',374,'es','/biblioteca/public/uploads/covers/juegos-del-hambre.jpg','Estante F-4',NULL,200.00,240.00,'2025-01-10','2025-01-10 10:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-11 21:16:08'),
(14,'9780767908177','00000nam a2200000 i 4500','BIB-00000014','Breve historia de casi todo','[\"Bill Bryson\"]','{\"leader\": \"00000nam a2200000 i 4500\", \"controlfields\": {\"001\": \"BIB-00000014\", \"020\": \"9780767908177\", \"041\": \"es\"}, \"datafields\": {\"100\": {\"a\": \"Bill Bryson\"}, \"245\": {\"a\": \"Breve historia de casi todo\", \"b\": \"\"}, \"250\": {\"a\": \"\"}, \"260\": {\"b\": \"Broadway Books\", \"c\": \"2003\"}, \"300\": {\"a\": \"560 p.\"}, \"520\": {\"a\": \"Un recorrido fascinante por las ciencias: desde el Big Bang hasta el origen de la vida, con humor e inteligencia. Bryson explica los misterios del universo sin sacrificar la profundidad.\"}, \"650\": {\"a\": [\"Ciencias Naturales\"]}, \"700\": {\"a\": [\"Bill Bryson\"]}, \"856\": {\"u\": \"\"}}}','Broadway Books',NULL,2003,2,NULL,'physical',NULL,NULL,NULL,NULL,'Un recorrido fascinante por las ciencias: desde el Big Bang hasta el origen de la vida, con humor e inteligencia. Bryson explica los misterios del universo sin sacrificar la profundidad.',560,'es','/biblioteca/public/uploads/covers/breve-historia-casi-todo.jpg','Estante B-2',NULL,240.00,280.00,'2025-02-01','2025-02-01 10:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-11 15:31:22','2026-04-12 20:33:56'),
(15,'9786071614445','00000nam a2200000 i 4500',NULL,'El laberinto de la soledad','[\"Paz, Octavio\"]',NULL,'Fondo de Cultura Económica','2a ed.',1959,1,1,'physical','book',NULL,NULL,NULL,'Ensayo sobre la identidad del mexicano y su historia cultural.',NULL,'es',NULL,'A-1-01',NULL,180.00,250.00,'2023-03-10','2023-03-10 00:00:00',0,4,4,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(16,'9788491050421','00000nam a2200000 i 4500',NULL,'Cien años de soledad','[\"García Márquez, Gabriel\"]',NULL,'Alfaguara','1a ed. conmem.',1967,1,1,'physical','book',NULL,NULL,NULL,'Novela cumbre del realismo mágico latinoamericano.',NULL,'es',NULL,'A-1-02',NULL,210.00,290.00,'2023-04-15','2023-04-15 00:00:00',0,5,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(17,'9786071627957','00000nam a2200000 i 4500',NULL,'Ficciones','[\"Borges, Jorge Luis\"]',NULL,'Alianza Editorial',NULL,1944,1,1,'physical','book',NULL,NULL,NULL,'Colección de cuentos fantásticos que mezclan filosofía y literatura.',NULL,'es',NULL,'A-1-03',NULL,150.00,220.00,'2023-05-20','2023-05-20 00:00:00',0,3,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(18,'9786074455366','00000nam a2200000 i 4500',NULL,'Introducción al cálculo diferencial e integral','[\"Stewart, James\"]',NULL,'Cengage Learning','8a ed.',2016,5,1,'physical','book',NULL,NULL,NULL,'Texto universitario estándar de cálculo con ejemplos aplicados.',NULL,'es',NULL,'B-2-01',NULL,520.00,680.00,'2023-06-01','2023-06-01 00:00:00',1,6,5,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(19,'9786071521835','00000nam a2200000 i 4500',NULL,'Historia general de México','[\"Cosío Villegas, Daniel\",\"Córdova, Arnaldo\"]',NULL,'El Colegio de México','3a ed.',2000,4,1,'physical','book',NULL,NULL,NULL,'Obra de referencia sobre la historia de México desde época prehispánica.',NULL,'es',NULL,'C-3-01',NULL,450.00,600.00,'2023-07-12','2023-07-12 00:00:00',0,3,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(20,NULL,'00000nam a2200000 i 4500',NULL,'Python para todos','[\"Severance, Charles R.\"]',NULL,'Dr. Chuck',NULL,2016,6,NULL,'digital','ebook',NULL,NULL,NULL,'Introducción a la programación con Python, disponible libremente en línea.',NULL,'es',NULL,NULL,NULL,0.00,0.00,'2023-01-05','2023-01-05 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(21,NULL,'00000nam a2200000 i 4500',NULL,'El mundo de Sofía (e-book)','[\"Gaarder, Jostein\"]',NULL,'Siruela',NULL,1991,8,NULL,'digital','ebook',NULL,NULL,NULL,'Novela filosófica que recorre la historia del pensamiento occidental.',NULL,'es',NULL,NULL,NULL,120.00,0.00,'2023-02-10','2023-02-10 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(22,NULL,'00000nam a2200000 i 4500',NULL,'Principios de economía','[\"Mankiw, N. Gregory\"]',NULL,'Cengage Learning','8a ed.',2018,13,NULL,'digital','ebook',NULL,NULL,NULL,'Manual universitario de economía con casos reales y ejercicios.',NULL,'es',NULL,NULL,NULL,350.00,0.00,'2023-03-22','2023-03-22 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(23,NULL,'00000nam a2200000 i 4500',NULL,'Sapiens: de animales a dioses','[\"Harari, Yuval Noah\"]',NULL,'Debate',NULL,2011,4,NULL,'digital','ebook',NULL,NULL,NULL,'Breve historia de la humanidad desde el Homo sapiens hasta la actualidad.',NULL,'es',NULL,NULL,NULL,199.00,0.00,'2023-04-18','2023-04-18 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(24,NULL,'00000nam a2200000 i 4500',NULL,'Inteligencia artificial: un enfoque moderno','[\"Russell, Stuart\",\"Norvig, Peter\"]',NULL,'Pearson','4a ed.',2020,6,NULL,'digital','ebook',NULL,NULL,NULL,'Texto de referencia mundial sobre inteligencia artificial.',NULL,'en',NULL,NULL,NULL,480.00,0.00,'2023-05-30','2023-05-30 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(25,NULL,'00000nas a2200000 i 4500',NULL,'Ciencia y Desarrollo','[]',NULL,'CONACYT',NULL,2023,2,1,'journal','journal',NULL,NULL,NULL,'Revista de divulgación científica y tecnológica publicada bimestralmente.',NULL,'es',NULL,'H-1-01',NULL,80.00,120.00,'2023-08-01','2023-08-01 00:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(26,NULL,'00000nas a2200000 i 4500',NULL,'Nexos','[]',NULL,'Nexos Sociedad Ciencia y Literatura',NULL,2023,4,1,'journal','journal',NULL,NULL,NULL,'Revista mensual de análisis político, económico y cultural de México.',NULL,'es',NULL,'H-1-02',NULL,90.00,130.00,'2023-08-15','2023-08-15 00:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(27,NULL,'00000nas a2200000 i 4500',NULL,'Proceso','[]',NULL,'CISA Comunicación e Información',NULL,2023,4,1,'journal','journal',NULL,NULL,NULL,'Semanario de periodismo de investigación con cobertura nacional.',NULL,'es',NULL,'H-1-03',NULL,75.00,110.00,'2023-09-01','2023-09-01 00:00:00',0,4,4,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(28,NULL,'00000nas a2200000 i 4500',NULL,'National Geographic en Español','[]',NULL,'The Walt Disney Company México',NULL,2023,2,1,'journal','journal',NULL,NULL,NULL,'Revista mensual de geografía, naturaleza y cultura.',NULL,'es',NULL,'H-1-04',NULL,95.00,140.00,'2023-09-20','2023-09-20 00:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(29,NULL,'00000nas a2200000 i 4500',NULL,'Letras Libres','[]',NULL,'Editorial Vuelta',NULL,2023,1,1,'journal','journal',NULL,NULL,NULL,'Revista mensual de ideas, arte y cultura contemporánea.',NULL,'es',NULL,'H-1-05',NULL,85.00,125.00,'2023-10-05','2023-10-05 00:00:00',1,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(30,NULL,'00000naa a2200000 i 4500',NULL,'El impacto de la IA en la educación superior','[\"Ramírez Torres, Luis\",\"González Pérez, Ana\"]',NULL,'Revista Latinoamericana de Educación',NULL,2023,10,NULL,'digital','article',NULL,NULL,NULL,'Análisis de casos de uso de inteligencia artificial en universidades latinoamericanas.',NULL,'es',NULL,NULL,NULL,0.00,0.00,'2023-11-01','2023-11-01 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(31,NULL,'00000nam a2200000 i 4500',NULL,'Cambio climático y biodiversidad en México','[\"Hernández Ávila\",\"Sofía\"]','{\"leader\":\"00000nam a2200000 i 4500\",\"controlfields\":{\"001\":\"\",\"020\":\"\",\"041\":\"en\"},\"datafields\":{\"100\":{\"a\":\"Hernández Ávila\"},\"245\":{\"a\":\"Cambio climático y biodiversidad en México\",\"b\":\"\"},\"250\":{\"a\":\"\"},\"260\":{\"b\":\"Ecosistemas Mexicanos\",\"c\":\"2022\"},\"300\":{\"a\":\"\"},\"520\":{\"a\":\"Estudio sobre la pérdida de especies endémicas por efectos del calentamiento global.\"},\"650\":{\"a\":[]},\"700\":{\"a\":[]},\"856\":{\"u\":\"\"}},\"rda\":{\"resource_type\":\"article\",\"content_type\":\"\",\"media_type\":\"\",\"carrier_type\":\"\"}}','Ecosistemas Mexicanos',NULL,2022,2,NULL,'digital','article',NULL,NULL,NULL,'Estudio sobre la pérdida de especies endémicas por efectos del calentamiento global.',NULL,'en',NULL,NULL,NULL,NULL,0.00,NULL,'2023-06-15 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:50'),
(32,NULL,'00000naa a2200000 i 4500',NULL,'Derecho digital y privacidad en redes sociales','[\"Morales Castillo, Jorge\"]',NULL,'Revista Mexicana de Derecho',NULL,2023,12,NULL,'digital','article',NULL,NULL,NULL,'Análisis jurídico del marco regulatorio sobre datos personales en plataformas digitales.',NULL,'es',NULL,NULL,NULL,0.00,0.00,'2023-07-20','2023-07-20 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(33,NULL,'00000naa a2200000 i 4500',NULL,'Neuroplasticidad y aprendizaje en adultos','[\"Vega Salinas, Carmen\",\"Díaz Ruiz, Roberto\"]',NULL,'Psicología Contemporánea',NULL,2022,9,NULL,'digital','article',NULL,NULL,NULL,'Revisión de evidencia científica sobre la capacidad del cerebro adulto para reorganizarse.',NULL,'es',NULL,NULL,NULL,0.00,0.00,'2023-05-10','2023-05-10 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(34,NULL,'00000naa a2200000 i 4500',NULL,'Microeconomía del mercado informal en México','[\"Fuentes Blanco, Patricia\"]',NULL,'Economía UNAM',NULL,2023,13,NULL,'digital','article',NULL,NULL,NULL,'Medición y análisis del sector informal de la economía mexicana 2015-2022.',NULL,'es',NULL,NULL,NULL,0.00,0.00,'2023-08-30','2023-08-30 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(35,NULL,'00000nam a2200000 i 4500',NULL,'Análisis de algoritmos de compresión de imágenes médicas','[\"López Mendoza, Carlos Alberto\"]',NULL,'Universidad Nacional Autónoma de México',NULL,2022,6,1,'thesis','thesis',NULL,NULL,NULL,'Tesis de maestría. Comparativa de rendimiento entre JPEG2000, HEIF y WebP en diagnóstico por imagen.',NULL,'es',NULL,'T-1-01',NULL,0.00,50.00,'2022-12-10','2022-12-10 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(36,NULL,'00000nam a2200000 i 4500',NULL,'Impacto socioeconómico del turismo cultural en Oaxaca','[\"Ríos Gutiérrez, Mariana\"]',NULL,'Universidad Autónoma Benito Juárez de Oaxaca',NULL,2021,13,1,'thesis','thesis',NULL,NULL,NULL,'Tesis de licenciatura. Estudio de caso en comunidades indígenas receptoras de turismo.',NULL,'es',NULL,'T-1-02',NULL,0.00,50.00,'2022-03-15','2022-03-15 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(37,NULL,'00000nam a2200000 i 4500',NULL,'Diseño de un sistema fotovoltaico autónomo para zonas rurales','[\"Soto Reyes, Emilio\",\"Cruz Ibáñez, Daniela\"]',NULL,'Instituto Politécnico Nacional',NULL,2023,6,1,'thesis','thesis',NULL,NULL,NULL,'Tesis de ingeniería. Dimensionado y simulación de un sistema solar aislado para comunidades sin red eléctrica.',NULL,'es',NULL,'T-1-03',NULL,0.00,50.00,'2023-07-01','2023-07-01 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(38,NULL,'00000nam a2200000 i 4500',NULL,'La mujer en la narrativa de Rosario Castellanos','[\"Vargas Ponce, Leticia\"]',NULL,'Universidad de Guadalajara',NULL,2020,1,1,'thesis','thesis',NULL,NULL,NULL,'Tesis de maestría en literatura. Análisis de género en la obra narrativa de Castellanos.',NULL,'es',NULL,'T-1-04',NULL,0.00,50.00,'2021-09-20','2021-09-20 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(39,NULL,'00000nam a2200000 i 4500',NULL,'Evaluación de estrategias pedagógicas en matemáticas básicas','[\"Aguilar Sánchez, Hugo\"]',NULL,'Universidad Pedagógica Nacional',NULL,2023,10,1,'thesis','thesis',NULL,NULL,NULL,'Tesis de doctorado. Intervención didáctica en secundarias de la Ciudad de México.',NULL,'es',NULL,'T-1-05',NULL,0.00,50.00,'2023-09-05','2023-09-05 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(40,NULL,'00000nem a2200000 i 4500',NULL,'Mapa topográfico de la Sierra Madre Occidental','[\"Instituto Nacional de Estadística y Geografía\"]',NULL,'INEGI',NULL,2018,16,1,'map','map',NULL,NULL,NULL,'Mapa escala 1:250,000 con curvas de nivel de la Sierra Madre Occidental.',NULL,'es',NULL,'M-1-01',NULL,120.00,180.00,'2021-04-10','2021-04-10 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(41,NULL,'00000nem a2200000 i 4500',NULL,'Atlas de México','[\"Secretaría de Educación Pública\"]',NULL,'SEP / INEGI',NULL,2020,16,1,'map','map',NULL,NULL,NULL,'Atlas escolar con mapas físicos, políticos, económicos y sociales de México.',NULL,'es',NULL,'M-1-02',NULL,95.00,140.00,'2021-08-22','2021-08-22 00:00:00',0,3,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(42,NULL,'00000nem a2200000 i 4500',NULL,'Mapa geológico de la Cuenca de México','[\"Servicio Geológico Mexicano\"]',NULL,'SGM',NULL,2015,2,1,'map','map',NULL,NULL,NULL,'Carta geológica escala 1:50,000 con zonificación de riesgos sísmicos.',NULL,'es',NULL,'M-1-03',NULL,80.00,120.00,'2022-01-15','2022-01-15 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(43,NULL,'00000nem a2200000 i 4500',NULL,'Mapa hidrológico de la Cuenca del Lerma-Santiago','[\"Comisión Nacional del Agua\"]',NULL,'CONAGUA',NULL,2019,16,1,'map','map',NULL,NULL,NULL,'Representación de cuencas, ríos y presas del sistema Lerma-Santiago.',NULL,'es',NULL,'M-1-04',NULL,100.00,150.00,'2022-06-30','2022-06-30 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(44,NULL,'00000nem a2200000 i 4500',NULL,'Atlas histórico de Mesoamérica','[\"Manzanilla, Linda\",\"Mirambell, Lorena\"]',NULL,'UNAM / IIA',NULL,2000,4,1,'map','map',NULL,NULL,NULL,'Colección de mapas arqueológicos y etnohistóricos de la región mesoamericana.',NULL,'es',NULL,'M-1-05',NULL,200.00,280.00,'2020-11-10','2020-11-10 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(45,NULL,'00000ncm a2200000 i 4500',NULL,'Sinfonía No. 5 en Do menor, Op. 67','[\"Beethoven, Ludwig van\"]',NULL,'Breitkopf & Härtel',NULL,1808,7,1,'score','score',NULL,NULL,NULL,'Partitura completa de orquesta para la quinta sinfonía de Beethoven.',NULL,'de',NULL,'P-1-01',NULL,350.00,500.00,'2020-02-14','2020-02-14 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(46,NULL,'00000ncm a2200000 i 4500',NULL,'Concierto de Aranjuez','[\"Rodrigo, Joaquín\"]',NULL,'Schott Music',NULL,1939,7,1,'score','score',NULL,NULL,NULL,'Partitura para guitarra y orquesta del célebre concierto español.',NULL,'es',NULL,'P-1-02',NULL,420.00,580.00,'2020-05-20','2020-05-20 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(47,NULL,'00000ncm a2200000 i 4500',NULL,'Suite Iberia para piano','[\"Albéniz, Isaac\"]',NULL,'Universal Edition',NULL,1909,7,1,'score','score',NULL,NULL,NULL,'Doce piezas para piano divididas en cuatro cuadernos.',NULL,'es',NULL,'P-1-03',NULL,280.00,390.00,'2021-03-08','2021-03-08 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(48,NULL,'00000ncm a2200000 i 4500',NULL,'Huapango de Moncayo','[\"Moncayo, José Pablo\"]',NULL,'SACM / UNAM',NULL,1941,7,1,'score','score',NULL,NULL,NULL,'Partitura orquestal del poema sinfónico basado en ritmos del Veracruz.',NULL,'es',NULL,'P-1-04',NULL,180.00,260.00,'2021-09-15','2021-09-15 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(49,NULL,'00000ncm a2200000 i 4500',NULL,'Nocturnes Op. 9, Nos. 1–3 (piano)','[\"Chopin, Frédéric\"]',NULL,'Peters Edition',NULL,1833,7,1,'score','score',NULL,NULL,NULL,'Tres nocturnos para piano, edición urtext revisada.',NULL,'fr',NULL,'P-1-05',NULL,190.00,270.00,'2022-04-22','2022-04-22 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(50,NULL,'00000ngm a2200000 i 4500',NULL,'Amores perros','[\"González Iñárritu, Alejandro\"]',NULL,'Altavista Films',NULL,2000,7,1,'audiovisual','audiovisual',NULL,NULL,NULL,'Película mexicana. Tres historias entrelazadas en torno a un accidente en la Ciudad de México.',NULL,'es',NULL,'AV-1-01',NULL,90.00,130.00,'2021-07-10','2021-07-10 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(51,NULL,'00000ngm a2200000 i 4500',NULL,'Cosmos: una odisea en el espacio-tiempo','[\"Druyan, Ann\",\"Soter, Steven\"]',NULL,'Fox Broadcasting / National Geographic',NULL,2014,2,1,'audiovisual','audiovisual',NULL,NULL,NULL,'Serie documental de 13 episodios presentada por Neil deGrasse Tyson.',NULL,'es',NULL,'AV-1-02',NULL,250.00,350.00,'2021-11-05','2021-11-05 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 20:36:16'),
(52,NULL,'00000ngm a2200000 i 4500',NULL,'La historia de México (serie documental)','[\"Canal Once\"]',NULL,'CIRT / Once TV',NULL,2010,4,1,'audiovisual','audiovisual',NULL,NULL,NULL,'Documental en 10 capítulos sobre la historia de México desde la conquista.',NULL,'es',NULL,'AV-1-03',NULL,0.00,80.00,'2022-02-18','2022-02-18 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(53,NULL,'00000ngm a2200000 i 4500',NULL,'El señor de los anillos: la trilogía (DVD)','[\"Jackson, Peter\"]',NULL,'New Line Cinema / Warner Bros.',NULL,2001,1,1,'audiovisual','audiovisual',NULL,NULL,NULL,'Adaptación cinematográfica de la trilogía de J.R.R. Tolkien. Versiones extendidas.',NULL,'es',NULL,'AV-1-04',NULL,380.00,500.00,'2022-08-12','2022-08-12 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(54,NULL,'00000ngm a2200000 i 4500',NULL,'Aprende inglés con videos BBC','[\"BBC Learning English\"]',NULL,'BBC',NULL,2019,11,1,'audiovisual','audiovisual',NULL,NULL,NULL,'Colección en USB de lecciones audiovisuales para aprendizaje de inglés A1-B2.',NULL,'en',NULL,'AV-1-05',NULL,150.00,200.00,'2023-01-25','2023-01-25 00:00:00',1,3,3,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(55,NULL,'00000nrm a2200000 i 4500',NULL,'Scrabble en español','[]',NULL,'Mattel',NULL,2015,11,1,'game','game',NULL,NULL,NULL,'Juego de mesa de formación de palabras con fichas de letras. 2-4 jugadores.',NULL,'es',NULL,'J-1-01',NULL,320.00,450.00,'2021-05-03','2021-05-03 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(56,NULL,'00000nrm a2200000 i 4500',NULL,'Pandemic (Pandemia)','[]',NULL,'Z-Man Games / Filosofia',NULL,2008,2,1,'game','game',NULL,NULL,NULL,'Juego cooperativo de mesa sobre control de enfermedades globales. 2-4 jugadores.',NULL,'es',NULL,'J-1-02',NULL,680.00,850.00,'2021-10-14','2021-10-14 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(57,NULL,'00000nrm a2200000 i 4500',NULL,'Catan (Los Colonos de Catán)','[]',NULL,'Kosmos / Devir',NULL,1995,13,1,'game','game',NULL,NULL,NULL,'Juego de estrategia y comercio de recursos. 3-4 jugadores.',NULL,'es',NULL,'J-1-03',NULL,750.00,950.00,'2022-03-28','2022-03-28 00:00:00',0,1,0,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(58,NULL,'00000nrm a2200000 i 4500',NULL,'Dixit','[]',NULL,'Libellud / Asmodee',NULL,2008,7,1,'game','game',NULL,NULL,NULL,'Juego de imaginación y narración con cartas ilustradas. 3-6 jugadores.',NULL,'es',NULL,'J-1-04',NULL,490.00,650.00,'2022-09-09','2022-09-09 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(59,NULL,'00000nrm a2200000 i 4500',NULL,'Ajedrez de torneo','[]',NULL,'DGT / House of Staunton',NULL,2020,5,1,'game','game',NULL,NULL,NULL,'Juego de ajedrez con piezas staunton no. 4 y tablero de madera.',NULL,'es',NULL,'J-1-05',NULL,900.00,1100.00,'2023-02-14','2023-02-14 00:00:00',1,3,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 19:37:52'),
(60,NULL,'00000nom a2200000 i 4500',NULL,'Kit de astronomía básica','[]',NULL,'Celestron / Biblioteca',NULL,2022,2,1,'kit','kit',NULL,NULL,NULL,'Kit compuesto por telescopio refractor 70mm, guía celeste y 2 mapas estelares.',NULL,'es',NULL,'K-1-01',NULL,1200.00,1500.00,'2022-10-01','2022-10-01 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(61,NULL,'00000nom a2200000 i 4500',NULL,'Kit de robótica LEGO Mindstorms','[]',NULL,'LEGO Education',NULL,2020,6,1,'kit','kit',NULL,NULL,NULL,'Kit educativo de robótica con sensores, motores y guía de proyectos.',NULL,'es',NULL,'K-1-02',NULL,4500.00,5500.00,'2022-11-15','2022-11-15 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(62,NULL,'00000nom a2200000 i 4500',NULL,'Kit de laboratorio de química básica','[]',NULL,'Carolina Biological Supply',NULL,2019,2,1,'kit','kit',NULL,NULL,NULL,'Kit de material de laboratorio para secundaria: tubos de ensayo, reactivos básicos y manual.',NULL,'es',NULL,'K-1-03',NULL,800.00,1100.00,'2023-01-10','2023-01-10 00:00:00',1,3,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(63,NULL,'00000nom a2200000 i 4500',NULL,'Kit de primeros auxilios pedagógico','[]',NULL,'Cruz Roja Mexicana',NULL,2021,14,1,'kit','kit',NULL,NULL,NULL,'Maletín con maniquí RCP, manual y materiales para talleres de primeros auxilios.',NULL,'es',NULL,'K-1-04',NULL,1500.00,2000.00,'2023-04-22','2023-04-22 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(64,NULL,'00000nom a2200000 i 4500',NULL,'Kit de impresión 3D básico','[]',NULL,'Creality / Biblioteca',NULL,2023,6,1,'kit','kit',NULL,NULL,NULL,'Impresora 3D Ender-3 ensamblada, 3 rollos de filamento PLA y guía de uso.',NULL,'es',NULL,'K-1-05',NULL,6000.00,7500.00,'2023-06-01','2023-06-01 00:00:00',1,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(65,NULL,'00000nam a2200000 i 4500',NULL,'Catálogo de la exposición Frida Kahlo','[\"Museo Dolores Olmedo\"]',NULL,'INBA / Museo Dolores Olmedo',NULL,2019,7,1,'other','other',NULL,NULL,NULL,'Catálogo de la exposición permanente con fichas técnicas y ensayos críticos.',NULL,'es',NULL,'O-1-01',NULL,280.00,380.00,'2021-06-15','2021-06-15 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(66,NULL,'00000nam a2200000 i 4500',NULL,'Calendario histórico prehispánico (reproducción)','[]',NULL,'INAH',NULL,2020,4,1,'other','other',NULL,NULL,NULL,'Reproducción del Calendario Azteca con guía de interpretación iconográfica.',NULL,'es',NULL,'O-1-02',NULL,150.00,200.00,'2022-01-20','2022-01-20 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(67,NULL,'00000nam a2200000 i 4500',NULL,'Diccionario de la Lengua Española (RAE)','[\"Real Academia Española\"]',NULL,'Espasa','23a ed.',2014,11,1,'other','other',NULL,NULL,NULL,'Edición en un tomo del diccionario normativo de la lengua española.',NULL,'es',NULL,'O-1-03',NULL,650.00,900.00,'2020-09-10','2020-09-10 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(68,NULL,'00000nam a2200000 i 4500',NULL,'Enciclopedia Britannica en español (15a edición)','[\"Encyclopaedia Britannica Inc.\"]',NULL,'Encyclopaedia Britannica','15a ed.',1985,17,1,'other','other',NULL,NULL,NULL,'Colección completa de 32 volúmenes de la Enciclopedia Britannica.',NULL,'es',NULL,'O-1-04',NULL,0.00,2500.00,'2019-03-05','2019-03-05 00:00:00',0,1,1,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07'),
(69,NULL,'00000nam a2200000 i 4500',NULL,'Manual de estilo Chicago-Deusto','[\"University of Chicago Press\"]',NULL,'Universidad de Deusto','17a ed.',2018,11,1,'other','other',NULL,NULL,NULL,'Guía de estilo editorial y citación académica. Referencia para investigadores.',NULL,'es',NULL,'O-1-05',NULL,320.00,450.00,'2022-05-18','2022-05-18 00:00:00',0,2,2,0,1,NULL,NULL,NULL,'2026-04-12 18:12:07','2026-04-12 18:12:07');
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_log`
--

DROP TABLE IF EXISTS `search_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `query` varchar(255) NOT NULL,
  `results` int(10) unsigned NOT NULL DEFAULT 0,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_search_log_user` (`user_id`),
  KEY `idx_search_log_created` (`created_at`),
  KEY `idx_search_log_query` (`query`(100)),
  CONSTRAINT `fk_search_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_log`
--

LOCK TABLES `search_log` WRITE;
/*!40000 ALTER TABLE `search_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL DEFAULT '',
  `type` enum('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES
('about_contact_badge','Encuéntranos','string','2026-04-12 21:11:58'),
('about_contact_title','Información de contacto','string','2026-04-12 21:11:58'),
('about_hero_badge','Quiénes somos','string','2026-04-12 21:11:57'),
('about_hero_subtitle','ededede','string','2026-04-12 21:12:14'),
('about_hero_title','dddd','string','2026-04-12 21:12:14'),
('about_history_badge','Nuestra historia','string','2026-04-12 21:11:58'),
('about_history_p1','Fundada con el propósito de democratizar el acceso al conocimiento, nuestra biblioteca ha sido desde sus inicios un punto de encuentro para estudiantes, investigadores, familias y amantes de la lectura.','string','2026-04-12 21:11:58'),
('about_history_p2','A lo largo de los años hemos ampliado nuestra colección, modernizado nuestros espacios y adaptado nuestros servicios a las nuevas necesidades digitales, sin perder jamás la calidez del trato humano que nos caracteriza.','string','2026-04-12 21:11:58'),
('about_history_p3','Hoy contamos con un amplio catálogo físico y digital, préstamos a domicilio, salas de estudio y un equipo de bibliotecarios comprometidos con guiar a cada visitante en su búsqueda del saber.','string','2026-04-12 21:11:58'),
('about_history_text','Fundada con el propósito de democratizar el acceso al conocimiento, nuestra biblioteca ha sido desde sus inicios un punto de encuentro para estudiantes, investigadores, familias y amantes de la lectura.\n\nA lo largo de los años hemos ampliado nuestra colección, modernizado nuestros espacios y adaptado nuestros servicios a las nuevas necesidades digitales, sin perder jamás la calidez del trato humano que nos caracteriza.\n\nHoy contamos con un amplio catálogo físico y digital, préstamos a domicilio, salas de estudio y un equipo de bibliotecarios comprometidos con guiar a cada visitante en su búsqueda del saber.','string','2026-04-12 21:18:15'),
('about_history_title','Más de una década al servicio de la comunidad','string','2026-04-12 21:11:58'),
('about_mission_text','Promover el acceso libre al conocimiento y fomentar el hábito lector en nuestra comunidad, ofreciendo un espacio acogedor, inclusivo y actualizado para todas las edades.','string','2026-04-12 21:11:58'),
('about_mission_title','Misión','string','2026-04-12 21:11:58'),
('about_timeline_items','2010|Apertura de la biblioteca con una colección inicial de 2 000 volúmenes.\r\n2014|Inauguración de la sala infantil y programa de animación lectora.\r\n2017|Lanzamiento del catálogo en línea y las primeras suscripciones digitales.\r\n2020|Adaptación a servicios remotos y expansión del fondo digital durante la pandemia.\r\n2023|Renovación de instalaciones y apertura de sala de co-trabajo.\r\n2025|Más de 10 000 socios activos y 50 000 préstamos anuales.','string','2026-04-12 21:12:14'),
('about_values_items','Acceso libre e igualitario\r\nRespeto e inclusión\r\nCompromiso con la educación\r\nInnovación y mejora continua\r\nTransparencia y servicio','string','2026-04-12 21:12:14'),
('about_values_title','Valores','string','2026-04-12 21:11:58'),
('about_vision_text','Ser el centro cultural de referencia de la región, reconocida por la excelencia de sus servicios, la riqueza de su colección y su compromiso con la educación permanente.','string','2026-04-12 21:11:58'),
('about_vision_title','Visión','string','2026-04-12 21:11:58'),
('app_url','','string','2026-04-12 14:30:09'),
('block_loans_with_fines','true','boolean','2026-04-12 12:27:35'),
('carnet_prefix','ISTEL','string','2026-04-12 12:08:38'),
('currency_symbol','$','string','2026-04-11 14:32:56'),
('date_format','d/m/Y H:i','string','2026-04-11 14:32:56'),
('fine_per_hour','0.05','decimal','2026-04-11 14:32:56'),
('library_address','Av. Principal #100, Centro','string','2026-04-11 14:44:42'),
('library_email','contacto@biblioteca.local','string','2026-04-11 14:44:42'),
('library_favicon','','string','2026-04-11 14:32:56'),
('library_logo','','string','2026-04-11 14:32:56'),
('library_name','BIBLIOTECA ISTEL','string','2026-04-12 12:26:56'),
('library_phone','+52 555 123 4567','string','2026-04-11 14:44:42'),
('library_schedule','Lunes a Viernes: 8:00 - 20:00 | Sábados: 9:00 - 14:00','string','2026-04-11 14:44:42'),
('library_slogan','Tu biblioteca de confianza','string','2026-04-11 14:44:42'),
('library_website','https://biblioteca.istel.edu.ec','string','2026-04-12 12:27:20'),
('loan_hours','72','integer','2026-04-11 14:32:56'),
('loan_hours_extended','120','integer','2026-04-11 14:32:56'),
('locale','es_MX','string','2026-04-11 14:32:56'),
('max_fine_multiplier','2.00','decimal','2026-04-11 21:29:48'),
('max_loans_per_user','3','integer','2026-04-12 08:14:33'),
('max_renewals','2','integer','2026-04-11 14:32:56'),
('new_acquisition_days','30','integer','2026-04-11 14:32:56'),
('news_on_home','3','integer','2026-04-11 14:32:56'),
('reminder_hours_before','24','integer','2026-04-11 14:32:56'),
('renewal_grace_hours','2','integer','2026-04-11 14:32:56'),
('reservation_hold_hours','48','integer','2026-04-11 14:32:56'),
('smtp_enabled','false','boolean','2026-04-12 11:58:51'),
('smtp_encryption','tls','string','2026-04-12 12:41:30'),
('smtp_from_address','biblioteca@istel.edu.ec','string','2026-04-12 12:41:30'),
('smtp_from_name','Biblioteca ISTEL','string','2026-04-12 12:50:06'),
('smtp_host','smtp.gmail.com','string','2026-04-12 12:41:30'),
('smtp_password','kbjgvouocltsbcfn','string','2026-04-12 12:41:30'),
('smtp_port','587','integer','2026-04-12 11:58:51'),
('smtp_timeout','30','integer','2026-04-12 11:58:51'),
('smtp_username','biblioteca_notificaciones@istel.edu.ec','string','2026-04-12 13:19:01'),
('timezone','America/Mexico_City','string','2026-04-11 14:32:56');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_group_students`
--

DROP TABLE IF EXISTS `teacher_group_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_group_students` (
  `group_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`group_id`,`student_id`),
  KEY `idx_tgs_student` (`student_id`),
  CONSTRAINT `fk_tgs_group` FOREIGN KEY (`group_id`) REFERENCES `teacher_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tgs_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_group_students`
--

LOCK TABLES `teacher_group_students` WRITE;
/*!40000 ALTER TABLE `teacher_group_students` DISABLE KEYS */;
INSERT INTO `teacher_group_students` VALUES
(1,3,'2026-04-11 14:39:04');
/*!40000 ALTER TABLE `teacher_group_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_groups`
--

DROP TABLE IF EXISTS `teacher_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `school_year` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_teacher_groups_teacher` (`teacher_id`),
  KEY `idx_teacher_groups_year` (`school_year`),
  CONSTRAINT `fk_teacher_groups_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_groups`
--

LOCK TABLES `teacher_groups` WRITE;
/*!40000 ALTER TABLE `teacher_groups` DISABLE KEYS */;
INSERT INTO `teacher_groups` VALUES
(1,2,'Literatura 3°A','Grupo de lectura de tercer año sección A','2024-2025',1,'2026-04-11 14:39:04','2026-04-11 14:39:04');
/*!40000 ALTER TABLE `teacher_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_number` varchar(20) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `document_number` varchar(30) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `role` enum('admin','librarian','teacher','user','guest') NOT NULL DEFAULT 'user',
  `user_type` enum('student','teacher','external','staff') NOT NULL DEFAULT 'student',
  `status` enum('active','suspended','blocked','inactive') NOT NULL DEFAULT 'active',
  `password_hash` varchar(255) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_document` (`document_number`),
  UNIQUE KEY `uq_users_user_number` (`user_number`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'2026-00001','Administrador del Sistema','admin@biblioteca.local','ADMIN-001',NULL,NULL,NULL,NULL,'admin','student','active','$argon2id$v=19$m=65536,t=4,p=1$QllZTkh2MWc0QldlRHBMZQ$dnOCHf1wPp3j6Zn/wpl5ddj30WFY1OsBeGnGCdGQ9EM',NULL,NULL,NULL,1,'2026-04-12 20:51:23','127.0.0.1','2026-04-11 14:39:04','2026-04-12 21:51:37'),
(2,'2026-00002','María García López','docente@biblioteca.local','DOC-001','0989026071',NULL,NULL,NULL,'teacher','teacher','active','$argon2id$v=19$m=65536,t=4,p=1$SDZ0RDdwLmIyZDFROGp2WQ$CXS6nB3LVCuWhwGwjVmcXG4YZayGt7lh7vytb65GaSo',NULL,NULL,NULL,1,'2026-04-11 18:04:14','127.0.0.1','2026-04-11 14:39:04','2026-04-12 18:29:43'),
(3,'2026-00003','Carlos Hernández Ruiz d','estudiante1@biblioteca.local','EST-001',NULL,NULL,NULL,NULL,'user','student','active','$argon2id$v=19$m=65536,t=4,p=1$ckt6aW5XZEtPdlBRTUV2aA$uGMtgy+Lw/ruMpYz3n8vLn7XMxJ+kO+Eand2t9TnflQ',NULL,NULL,NULL,1,'2026-04-11 18:00:52','127.0.0.1','2026-04-11 14:39:04','2026-04-12 17:49:14'),
(192,'2026-00004','CLAUDIO XAVIER BORJA SALTOS','info@softecsa.com','0201975844',NULL,NULL,NULL,NULL,'teacher','teacher','active','$argon2id$v=19$m=65536,t=4,p=1$djRGNkYyWXpKdWw1clJPNA$JmNQeEOn9ESL3+F2KCufaorDYf2iIUlmg9fH5FVKUy0',NULL,NULL,'2026-04-12 13:32:16',0,'2026-04-12 20:51:54','127.0.0.1','2026-04-12 14:31:24','2026-04-12 21:56:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visits_log`
--

DROP TABLE IF EXISTS `visits_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visits_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `page` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referer` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_visits_log_user` (`user_id`),
  KEY `idx_visits_log_branch` (`branch_id`),
  KEY `idx_visits_log_page` (`page`(100)),
  KEY `idx_visits_log_created` (`created_at`),
  CONSTRAINT `fk_visits_log_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visits_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visits_log`
--

LOCK TABLES `visits_log` WRITE;
/*!40000 ALTER TABLE `visits_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `visits_log` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-12 22:03:59
