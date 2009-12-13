// this code will be generated with PHP

function fill_in_data() {
    var teachers = [];
    var entries = [];
    entries.push(newEntry(1, '08:20', 'H1-2', 'Ausfall'));
    entries.push(newEntry(2, '09:50', 'H2-3', 'Geschichte'));
    teachers.push(newTeacher('Hr. Beispiel', entries));
    var entries = [];
    entries.push(newEntry(3, '09:50', 'H1-2', 'Mathe'));
    entries.push(newEntry(4, '14:30', 'H2-3', 'Deutsch'));
    teachers.push(newTeacher('Fr. Exempel', entries));
    addDay('Montag, 14.12.2009', teachers);
    var teachers = [];
    var entries = [];
    entries.push(newEntry(5, '08:20', 'H1-2', 'Sport'));
    entries.push(newEntry(6, '11:05', 'H2-3', 'Erdkunde'));
    teachers.push(newTeacher('Hr. Beispiel', entries));
    var entries = [];
    entries.push(newEntry(7, '08:35', 'H1-2', 'Englisch'));
    entries.push(newEntry(8, '13:05', 'H2-3', 'Ausfall'));
    teachers.push(newTeacher('Fr. Exempel', entries));
    addDay('Dienstag, 15.12.2009', teachers);
}
