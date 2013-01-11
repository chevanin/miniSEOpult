<?php
// дл€ URL-ов
$RegexFilterArray = array(
	"board",
	"(php|ya|fast)bb",
	"phorum",
	"guest",
	"gbs",
	"gostevaja",
	"forum",
	"view(profile|topic|thread)",
	"show(post|topic|thread|comments|user)",
	"printthread",
	"akobook",
	"ns-comments",
	"datsogallery",
	"gbook",
	"thread\.php",
);

// —топ-слова дл€ текста и ссылок
$StopWordsMasks = array(
    "порн(о|уха)",
);

// ƒопустимое кол-во стоп-слов в тексте
$MaxAvailCountInText = 1;
