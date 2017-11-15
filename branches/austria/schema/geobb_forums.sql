-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u8
-- http://www.phpmyadmin.net
--
-- Host: vwp1693.webpack.hosteurope.de
-- Erstellungszeit: 15. Nov 2017 um 02:35
-- Server Version: 5.6.36
-- PHP-Version: 5.4.45-0+deb7u11

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `db1036181-atgeo`
--

--
-- Daten für Tabelle `geobb_forums`
--

INSERT INTO `geobb_forums` (`forum_id`, `forum_name`, `forum_desc`, `forum_order`, `forum_icon`, `topics_count`, `posts_count`) VALUES
(1, 'Ankündigungen', 'Projektspezifische Ankündigungen', 0, 'announce.gif', 0, 0),
(2, 'Allgemeine Diskussion', 'Allgemeine Diskussion über das Projekt', 1, 'general.gif', 0, 0),
(3, 'Anregungen', 'Was könnten wir besser machen? Was fehlt?', 2, 'suggestions.gif', 0, 0),
(4, 'Technische Fehler', 'Fehler und Probleme', 3, 'bugs.gif', 0, 0),
(5, 'Planquadrate', 'Diskussionen über Planquadrate. Neue Diskussionen können über den Link auf der Foto-Seite begonnen werden.', 4, 'gridsquare.gif', 0, 0),
(6, 'Benutzerbeiträge', 'Von Benutzern erstellte Beiträge und Galerien', 5, 'articles.gif', 0, 0),
(7, 'Galerien', 'Thematische Galerien', 6, 'gallery.gif', 0, 0),
(8, 'Moderation', 'Diskussion zur Moderation', 7, 'mod.gif', 0, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
