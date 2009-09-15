Format von data.txt:

 OFFLINE
=========

Tag/Uhrzeit	Lehrer			Fach	Dauer	Klasse/Jahrgang	Vertretender Lehrer	Änderung
----------------------------------------------------------------------------------------------------------------
Montag, 8.20	Herr Wüstenberg		Ma-LK	75	13		-			H0-2		// Raumänderung
Montag, 8.20	Herr Wüstenberg		Ma-LK	75	13		Frau Lange		-		// Vertretung
Montag, 8.20	Herr Wüstenberg		Ma-LK	75	13		-			Ausfall		// Ausfall
Montag, 8.20	Herr Wüstenberg		Ma-LK	75	13		Frau Lange		Geschichte	// Vertretung mit Fachwechsel
Montag, 8.20	Herr Wüstenberg		Ma-LK	75	13		Frau Lange		Geschichte H0-2	// Vertretung mit Fach- und Raumwechsel


 ONLINE
========

Tag/Uhrzeit	Fach	Klasse	Originalraum	Änderung
------------------------------------------------------------------------
Montag, 8.20	Ma-LK	13	H1-4		H0-2			// Raumänderung
Montag, 8.20	Ma-LK	13	H1-4		Ausfall			// Ausfall
Montag, 8.20	Ma-LK	13	H1-4		Geschichte		// Vertretung mit Fachwechsel
Montag, 8.20	Ma-LK	13	H1-4		Geschichte H0-2		// Vertretung mit Fach- und Raumwechsel


Beispiel:

1234567890,Herr Wüstenberg,Ma-LK,75,13,H1-4,Frau Lange,Geschichte in Raum H0-2 // data.txt
++++++++++,---------------,+++++,--,++,++++,----------,+++++++++++++++++++++++ // online view
++++++++++,+++++++++++++++,+++++,++,++,----,++++++++++,+++++++++++++++++++++++ // printout
