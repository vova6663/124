-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Фев 23 2026 г., 15:21
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `green_mile`
--
CREATE DATABASE IF NOT EXISTS `green_mile` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `green_mile`;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `message`, `recipient_id`, `is_read`, `created_at`) VALUES
(1, 1, 'jyhtdfytfrijk', 6, 0, '2026-02-19 11:29:12'),
(2, 1, 'вапвап', 7, 0, '2026-02-19 11:37:37'),
(3, 1, 'вапвап', 5, 0, '2026-02-19 11:37:43'),
(4, 11, 'hjg', 6, 0, '2026-02-22 11:49:49'),
(5, 6, 'lf sdjhfj', NULL, 0, '2026-02-22 11:50:34'),
(6, 3, 'готово', NULL, 0, '2026-02-22 13:06:26'),
(7, 3, 'Чат', NULL, 0, '2026-02-22 13:27:57'),
(8, 3, 'Ну ты пиздец Дим', NULL, 0, '2026-02-22 13:30:52'),
(9, 11, 'я с тебя в ахуе', NULL, 0, '2026-02-22 13:31:09'),
(10, 11, 'пзд', 3, 0, '2026-02-22 13:31:43'),
(11, 5, 'Соси слава', NULL, 0, '2026-02-22 13:44:03'),
(12, 5, 'Я тестер я так вижу', NULL, 0, '2026-02-22 13:44:14'),
(13, 12, 'ти хто?', 10, 0, '2026-02-22 13:48:07'),
(14, 12, 'что ты видишь?', 5, 0, '2026-02-22 13:48:37'),
(15, 12, 'а?', 5, 0, '2026-02-22 13:48:49'),
(16, 14, 'Ну вроде всё что нужно проверил как исправите', NULL, 0, '2026-02-22 13:55:46'),
(17, 14, 'Пишите', NULL, 0, '2026-02-22 13:55:50'),
(18, 22, 'Ну вроде всё', NULL, 0, '2026-02-23 11:43:05'),
(19, 24, 'Работает', NULL, 0, '2026-02-23 13:15:48');

-- --------------------------------------------------------

--
-- Структура таблицы `client`
--

CREATE TABLE `client` (
  `id_Client` int(11) NOT NULL,
  `INN` varchar(255) DEFAULT NULL,
  `addres` varchar(255) DEFAULT NULL,
  `con` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `client`
--

INSERT INTO `client` (`id_Client`, `INN`, `addres`, `con`) VALUES
(1, '1234567890\r\n', 'ул. Ленина, 15, офис 3\r\n', 'ООО \"ОфисСервис\"\r\n'),
(2, '2345678901\r\n', 'пр. Победы, 45, ТЦ \"Мега\"\r\n', 'ТЦ \"Мега\"\r\n'),
(3, '3456789012\r\n', 'ул. Кирова, 78, 2 этаж\r\n', 'Сеть супермаркетов \"Продукты\"\r\n'),
(4, '4567890123', 'ул. Советская, 12', 'Школа №15'),
(5, '5678901234', 'ул. Гагарина, 34, корпус А\r\n', 'БЦ \"Гагаринский\"\r\n'),
(6, '6789012345\r\n', 'ул. Мира, 89', 'Больница №3\r\n'),
(7, '7890123456\r\n', 'пр. Строителей, 23\r\n', 'Завод \"Электрон\"\r\n'),
(8, '8901234567\r\n', 'ул. Пушкинская, 56\r\n', 'Университет им. Пушкина\r\n'),
(9, '9012345678\r\n', 'ул. Садовая, 41, офис 10\r\n', 'ООО \"БизнесКонсалт\"\r\n'),
(10, '123456789\r\n', 'ул. Молодежная, 7, ТЦ \"Радуга\"\r\n', 'ТЦ \"Радуга\"\r\n');

-- --------------------------------------------------------

--
-- Структура таблицы `complete_orders`
--

CREATE TABLE `complete_orders` (
  `id_C_orders` int(11) NOT NULL,
  `comments` text NOT NULL,
  `id_transport` int(11) DEFAULT NULL,
  `id_order` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo1` varchar(255) DEFAULT NULL,
  `photo2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `complete_orders`
--

INSERT INTO `complete_orders` (`id_C_orders`, `comments`, `id_transport`, `id_order`, `photo`, `photo1`, `photo2`) VALUES
(1, '', 1, 1, 'zakaz1_1.jpg\r\n', 'zakaz1_2.jpg\r\n', 'zakaz1_3.jpg\r\n'),
(2, '', 3, 3, '1771499726_Skl.ico', 'zakaz3_2.jpg\r\n', 'zakaz3_3.jpg\r\n'),
(3, 'Задержка на 15 минут из-за пробок\r\n', 6, 6, 'zakaz6_1.jpg\r\n', 'zakaz6_2.jpg\r\n', 'zakaz6_3.jpg\r\n'),
(4, '', 1, 9, 'zakaz9_1.jpg\r\n', 'zakaz9_2.jpg\r\n', 'zakaz9_3.jpg\r\n'),
(5, 'Клиент недоволен временем ожидания\r\n', 4, 2, 'zakaz2_1.jpg\r\n', 'zakaz2_2.jpg\r\n', 'zakaz2_3.jpg\r\n'),
(6, '', 5, 5, '1771855260_1_5.jpg', '1771855260_2_5.jpg', '1771855260_3_5.jpg'),
(7, '', 8, 8, 'zakaz8_1.jpg\r\n', 'zakaz8_2.jpg\r\n', 'zakaz8_3.jpg\r\n'),
(8, '', 2, 7, 'zakaz7_1.jpg\r\n', 'zakaz7_2.jpg\r\n', 'zakaz7_3.jpg\r\n'),
(9, 'Потребовалась дополнительная тара\r\n', 9, 4, 'zakaz4_1.jpg\r\n', 'zakaz4_2.jpg\r\n', 'zakaz4_3.jpg\r\n'),
(13, 'Назначено диспетчером', 6, 12, '1771765569_Err_Blue.ico', NULL, NULL),
(14, 'Назначено диспетчером', 6, 13, NULL, NULL, NULL),
(17, 'Назначено диспетчером', 6, 19, NULL, NULL, NULL),
(18, 'Назначено диспетчером', 6, 21, NULL, NULL, NULL),
(19, 'Взят в работу', 6, 22, NULL, NULL, NULL),
(20, 'Взят в работу', 6, 17, NULL, NULL, NULL),
(21, 'Взят в работу', 6, 20, NULL, NULL, NULL),
(22, 'Взят в работу', 6, 23, NULL, NULL, NULL),
(23, 'Взят в работу', 6, 24, NULL, NULL, NULL),
(24, 'Взят в работу', 2, 26, NULL, NULL, NULL),
(25, 'Взят в работу', 4, 28, '1771846845_RAHHHH🗣🗣🗣.jpg', NULL, NULL),
(26, 'Взят в работу', 4, 40, NULL, NULL, NULL),
(27, 'Взят в работу', 4, 27, NULL, NULL, NULL),
(32, 'Назначено диспетчером', 4, 68, NULL, NULL, NULL),
(33, 'Назначено диспетчером', 2, 66, '1771853713_2.png', NULL, NULL),
(34, 'Назначено диспетчером', 6, 65, '1771855083_1_65.png', '1771855083_2_65.png', '1771855083_3_65.png'),
(35, 'Назначено диспетчером', 2, 63, NULL, NULL, NULL),
(36, 'Взят в работу', 6, 67, '1771855129_1_67.png', '1771855129_2_67.png', '1771855129_3_67.jpg');

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `materials`
--

CREATE TABLE `materials` (
  `id_material` int(11) NOT NULL,
  `Name_Mat` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `rate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `materials`
--

INSERT INTO `materials` (`id_material`, `Name_Mat`, `comments`, `rate`) VALUES
(1, 'Макулатура\r\n', 'Картон, бумага офисная, газеты\r\n', '8р за кг'),
(2, 'Пластик ПЭТ\r\n', 'Пластиковые бутылки\r\n', '15р за кг'),
(3, 'Пластик ПНД\r\n', 'Полиэтилен низкого давления\r\n', '18р за кг'),
(4, 'Стекло', 'Стеклянная тара', '3р за кг'),
(5, 'Металл черный', 'Жестяные банки, металлолом', '12 за кг'),
(6, 'Металл цветной\r\n', 'Алюминий, медь\r\n', '85 за кг'),
(7, 'Полиэтилен\r\n', 'Пленка, пакеты\r\n', '10 за кг'),
(8, 'Органика', 'Пищевые отходы (для компостирования)', '0 за кг'),
(9, 'Опасные отходы\r\n', 'Батарейки, лампы\r\n', '25 за шт'),
(10, 'Электроника\r\n', 'Старая техника\r\n', '25 за кг');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_client` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `data_Time` datetime DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `addres` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `id_materials` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id_order`, `id_client`, `route_id`, `status`, `data_Time`, `volume`, `addres`, `comments`, `id_materials`) VALUES
(1, NULL, 3, 2, '2026-01-02 09:00:00', '50', 'ул. Ленина, 15, офис 3', 'Макулатура из архива\r\n', 1),
(2, NULL, NULL, 5, '2025-02-01 10:30:00', '120', 'пр. Победы, 45, ТЦ \"Мега\"\r\n', 'Пластик ПЭТ от торгового центра\r\n', 2),
(3, NULL, NULL, 5, '2026-02-02 11:15:59', '80', 'ул. Кирова, 78\r\n', 'Органика от столовой\r\n', 8),
(4, NULL, NULL, 2, '2026-02-02 13:08:00', '4', 'ул. Советская, 12', 'Макулатура из школы\r\n', 1),
(5, NULL, NULL, 5, '2026-02-23 14:02:00', '151', 'ул. Гагарина, 34', 'Стеклянная тара из бизнес-центра\r\n', 9),
(6, NULL, NULL, 5, '2026-02-04 10:49:52', '300', 'ул. Мира, 89\r\n', 'Медицинские отходы (безопасные)\r\n', 7),
(7, NULL, NULL, 5, '2026-02-03 13:18:21', '180', 'пр. Строителей, 23\r\n', 'Металлолом с завода\r\n', 5),
(8, NULL, NULL, 2, '2026-02-03 16:35:21', '90', 'ул. Пушкинская, 56\r\n', 'Пластик ПНД из университета\r\n', 3),
(9, NULL, NULL, 3, '2026-02-04 18:42:56', '250', 'ул. Садовая, 41\r\n', 'Смешанные отходы из офиса\r\n', 1),
(12, 4, NULL, 5, '2026-02-22 17:29:00', '32', 'sdv', '', 1),
(13, NULL, NULL, 5, '2026-02-22 18:08:00', '2430', 'дк кристал', 'дк [ПРОБЛЕМА: Попал в Аварию]', 10),
(17, NULL, 3, 5, '2026-02-22 18:49:00', '10', 'wervfgftun', 'retyrtydhrthg', 5),
(19, NULL, NULL, 5, '2026-02-22 20:24:00', '3453', 'sdv', 'авпрнгнготплгртпгш', 6),
(20, 4, NULL, 5, '2026-02-23 14:35:00', '5435', 'лпаневарпл', '', 7),
(21, NULL, NULL, 5, '2026-02-23 14:46:00', '3465', 'ул. Советская, 12', 'аымвеврвирвр вериуеквр екрувекр сатв черв чвап впврвк атв', 9),
(22, NULL, NULL, 5, '2026-02-23 14:49:00', '2352', 'ул. Советская, 12', '', 6),
(23, 4, NULL, 5, '2026-02-23 15:43:00', '9999', 'ул. Советская, 12', 'йКУЦК ИВПФЦАКСЫУЕЫВ ЫУКП ', 3),
(24, NULL, NULL, 5, '2026-02-23 15:52:00', '9876', 'ул. Ленина, 15, офис 3', '', 6),
(26, NULL, NULL, 5, '2026-02-23 16:25:00', '3545', 'ул. Ленина, 15, офис 3', 'rtsdxfedrybx', 5),
(27, NULL, NULL, 5, '2026-02-23 16:26:00', '6556', 'ул. Ленина, 15, офис 3', '', 8),
(28, NULL, NULL, 5, '2026-02-23 16:31:00', '234', 'ул. Мира, 23', 'пмаиранаепгаимопртр [ПРОБЛЕМА: неробиит]', 3),
(40, 4, NULL, 5, '2026-02-23 16:36:00', '342', 'ул. Ленина, 15, офис 3', '', 6),
(63, 25, NULL, 2, '2026-02-23 17:27:00', '234', 'ул. Ленина, 15, офис 3', 'выап', 2),
(65, 25, NULL, 5, '2026-02-23 17:30:00', '4345', 'ул. Советская, 12', '', 9),
(66, NULL, NULL, 5, '2026-02-23 17:47:00', '235', 'ул. Мира, 23', '', 1),
(67, NULL, NULL, 5, '2026-02-23 15:57:00', '123', '11 микрайон, 13, Качканар, Свердловская обл., 624351', '', 6),
(68, NULL, NULL, 2, '2026-02-23 18:12:00', '3234', 'ул. Мира, 23', '', 10),
(69, 28, NULL, 2, '2026-02-23 16:17:00', '123', '11 микрайон, 13, Качканар, Свердловская обл., 624351', '', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `transport`
--

CREATE TABLE `transport` (
  `id_transport` int(11) NOT NULL,
  `Gos_N` varchar(255) DEFAULT NULL,
  `Model_transport` varchar(255) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `transport`
--

INSERT INTO `transport` (`id_transport`, `Gos_N`, `Model_transport`, `id_user`) VALUES
(2, 'А002ВС77\r\n', 'ГАЗель NEXT\r\n', 6),
(4, 'А004ВС77', 'Ford Transit', 26),
(5, 'А005ВС77', 'ГАЗель NEXT', 29),
(6, 'А006ВС77\r\n', 'Volkswagen Crafter\r\n', 3),
(7, 'А007ВС77\r\n', 'ГАЗель NEXT\r\n', 6),
(8, 'А008ВС77\r\n', 'Mercedes Sprinter\r\n', 7),
(9, 'А009ВС77\r\n', 'ГАЗ 3309 (мусоровоз)\r\n', 8),
(10, 'А010ВС77\r\n', 'ГАЗ 3309 (мусоровоз)\r\n', 9),
(11, 'stey.jldfx', 'sdgyuhjk', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `Login` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `First_Name` varchar(255) DEFAULT NULL,
  `Last_Name` varchar(255) DEFAULT NULL,
  `role` int(11) NOT NULL,
  `driver_status` enum('free','busy','offline') DEFAULT 'free'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id_user`, `Login`, `Password`, `First_Name`, `Last_Name`, `role`, `driver_status`) VALUES
(3, 'petrov_iv', 'demo123', 'Иван', 'Петров', 3, 'free'),
(4, 'sidorova_ma', 'demo123', 'Мария', 'Сидорова', 4, 'free'),
(5, 'kozlov_sa', 'demo123', 'Серкгей', 'Козлов', 2, 'free'),
(6, 'volkov_ai', 'demo123', 'Алексей', 'Волков', 3, 'busy'),
(9, 'frolov_mp', 'demo123', 'Михаил', 'Фролов', 3, 'busy'),
(10, 'zaitseva_n', 'demo123', 'Наталья', 'Зайцева', 2, 'free'),
(11, 'testuser', 'test123', 'Тестовый', 'Пользователь', 2, 'free'),
(24, 'nikolaeva_t', 'demo123', 'Курсовая', 'работа', 1, 'free'),
(25, 'testusera', 'test123', 'testusera', 'testusera', 4, 'free'),
(26, 'testvod', 'demo123', 'testvod', 'testvod', 3, 'busy'),
(27, 'a', 'a', 'a', 'a', 5, 'free'),
(28, 'b', 'b', 'b', 'b', 4, 'free'),
(29, 'c', 'c', 'c', 'c', 3, 'free'),
(30, 'd', 'd', 'd', 'd', 2, 'free'),
(31, 'e', 'e', 'e', 'e', 1, 'free');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_recipient` (`recipient_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Индексы таблицы `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id_Client`);

--
-- Индексы таблицы `complete_orders`
--
ALTER TABLE `complete_orders`
  ADD PRIMARY KEY (`id_C_orders`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id_material`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `fk_orders_client` (`id_client`),
  ADD KEY `fk_orders_materials` (`id_materials`);

--
-- Индексы таблицы `transport`
--
ALTER TABLE `transport`
  ADD PRIMARY KEY (`id_transport`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `client`
--
ALTER TABLE `client`
  MODIFY `id_Client` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `complete_orders`
--
ALTER TABLE `complete_orders`
  MODIFY `id_C_orders` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `materials`
--
ALTER TABLE `materials`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT для таблицы `transport`
--
ALTER TABLE `transport`
  MODIFY `id_transport` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_client` FOREIGN KEY (`id_client`) REFERENCES `client` (`id_Client`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_materials` FOREIGN KEY (`id_materials`) REFERENCES `materials` (`id_material`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
