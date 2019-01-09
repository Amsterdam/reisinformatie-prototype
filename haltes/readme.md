# Weergave toegankelijkheid haltes uit CHB

Deze code bestaat uit twee stappen:

* Het ophalen van de laatste versie van het CHB en daar voor één of meerdere steden de haltes uithalen en opslaan als geoJSON en CSV
* Een kaartweergave waarbij voor een stad de haltes worden getoond, waarbij in kleur de toegankelijkheid wordt weergegeven.

## Conversie

In de map conversie staat een PHP-bestand dat op http://data.ndovloket.nl/haltes/ zoekt naar de huidige CHB, deze ophaalt en lokaal opslaat en unzipt.
Vervolgens worden voor één of meerdere steden (in het huidige geval Amsterdam, Rotterdam, Den Haag, Utrecht, Eindhoven en Almere) de haltes opgeslagen in aparte bestanden, zowel in CSV als in GeoJSON.
Deze conversie zou dagelijks of wekelijks uitgevoerd kunnen worden om de data up-to-date te houden.

Vereisten: PHP 5+ en voldoende schijfruimte (ca 400MB) en werkgeheugen (min. 2GB) om conversie uit te voeren)

## Weergave

Eén HTML-bestand die de haltes in een Google Maps-kaartje presenteert, afhankelijk van de toegankelijkheid. Een klik op een kaart laat de details van een halte zien en een Streetview-weergave van de haltes.

Vereisten: Webserver en een Google API-key (nog toe te voegen)