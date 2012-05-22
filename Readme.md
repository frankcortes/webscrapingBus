# WebScrapingBus

> A very simple and tiny API REST to get the bus stops and timetables of Viladecans BUS in real time

## Use

Execute the apiBus.php from curl with these methods:

### refresh

Refresh the busLines API. Webscraping returns to be realised.

### getBusData (fileID)

Checks if the initial fileID is the same that the current file. If this ocurrs, then returns the new content.

### generateMarker

Generate Markers with the disponibled CSV data.

### markersWithRadius (lat,lng,radius,inputNameFile)

Return a list of markers indicating latitude and longitude, and a concrete radius.

### markersWithBusline(numLine,inputNameFile)
Return a list of markers indicating the numLine that contains them.


