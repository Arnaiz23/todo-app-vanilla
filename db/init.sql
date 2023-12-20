CREATE DATABASE IF NOT EXISTS todo;
USE todo;

CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `user` (id, username, password) VALUES (1, "adrian", "$2a$12$v2d8ydFJt3Jn1ukHNFdgwuEdHGC/FIRZDbAHflfuObzbMLJMMA7Aq");

CREATE TABLE `todo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `completed` tinyint(1) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`)
);

INSERT INTO `todo` (`id`, `title`, `completed`, `user_id`) VALUES
(1,	'Testing',	1,	1),
(2,	'Another',	0,	1),
(3,	'create',	0,	1),
(4,	'a',	0,	1);
