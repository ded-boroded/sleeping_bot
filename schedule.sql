SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `chat_name` text NOT NULL,
  `open_time` text NOT NULL,
  `close_time` text NOT NULL,
  `open` int(11) NOT NULL,
  `u` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;

