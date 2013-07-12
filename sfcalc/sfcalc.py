#!/usr/bin/python


# http://stackoverflow.com/questions/16015533/get-n-points-on-a-line
from geographiclib.geodesic import Geodesic
from Pysolar import solar
import datetime
import time
import math
import json
import sys

# get input
if (len(sys.argv) < 7):
	print "Usage:   sfcalc.py from_lat_lng to_lat_lng flight_mins departure_date departure_time gmt_offset"
	print "example: sfcalc.py -33.946,151.177 1.350,103.994 710 2013-06-15 09:05:00 10"
	sys.exit()

# get argv
from_lat_lon = sys.argv[1]
to_lat_lon = sys.argv[2]
flight_mins = int(sys.argv[3])
departure_time = datetime.datetime.strptime(sys.argv[4] + " " + sys.argv[5], "%Y-%m-%d %H:%M:%S")
departure_time = departure_time + datetime.timedelta(0,-60*60*int(sys.argv[6]))

# Sample input
# QF15 BNE to LAX 14 hours
#from_lat_lon = "-27.3988315,153.11779990000002" # BNE
#from_lat_lon = "-33.94611,151.17722" # SYD
#from_lat_lon = "-16.88583,145.75528" # CNS
#from_lat_lon = "-37.67333,144.84333" # MEL
#from_lat_lon = "-8.74817,115.16717" # DPS
#from_lat_lon = "51.47750,-0.46139" # LHR
#to_lat_lon = "33.943015000000000000,-118.409425199999990000" # LAX
#to_lat_lon = "32.89683,-97.03800" # DFW
#to_lat_lon = "1.35019,103.99443" # SIN
#to_lat_lon = "-37.67333,144.84333" # MEL
#to_lat_lon = "35.76472,140.38639" # NRT
#flight_mins = 710
#departure_time = datetime.datetime.strptime("2013-06-15 09:05:00", "%Y-%m-%d %H:%M:%S")
#departure_time = departure_time + datetime.timedelta(0,-60*60*10) # GMT + 10


# parse input
from_lat = float(from_lat_lon.split(",")[0])
from_lon = float(from_lat_lon.split(",")[1])
to_lat = float(to_lat_lon.split(",")[0])
to_lon = float(to_lat_lon.split(",")[1])
origin = from_lat, from_lon
destination = to_lat, to_lon

def main():
	calcPoints(origin, destination, flight_mins, departure_time)

# https://gist.github.com/geografa/1366401
def calcBearing(origin, destination):
	lat1, lon1 = origin
	lat2, lon2 = destination
 
	rlat1 = math.radians(lat1)
	rlat2 = math.radians(lat2)
	rlon1 = math.radians(lon1)
	rlon2 = math.radians(lon2)
	dlon = math.radians(lon2-lon1)
 
	b = math.atan2(math.sin(dlon)*math.cos(rlat2),math.cos(rlat1)*math.sin(rlat2)-math.sin(rlat1)*math.cos(rlat2)*math.cos(dlon)) # bearing calc
	bd = math.degrees(b)
	br,bn = divmod(bd+360,360) # the bearing remainder and final bearing
	
	return bn


def calcPoints(origin, destination, flight_mins, departure_time):

	total_mins_left = 0
	total_mins_right = 0
	total_mins_night = 0

	has_seen_sunset = False
	has_seen_sunrise = False
	mins_to_first_sunset = 0
	mins_to_first_sunrise = 0

	from_lat, from_lon = origin
	to_lat, to_lon = destination

	number_points = flight_mins # num minutes
	point_time = departure_time # departure time in UTC

	gd = Geodesic.WGS84.Inverse(from_lat, from_lon, to_lat, to_lon)
	line = Geodesic.WGS84.Line(gd['lat1'], gd['lon1'], gd['azi1'])

	bearing = None
	solar_altitude = 0
	last_solar_altitude = 0

	points = []
	for i in range(number_points + 1):

		point = line.Position(gd['s12'] / number_points * i)
		if ((i+1) < number_points):
			# calculat bearing
			point2 = line.Position(gd['s12'] / number_points * i+1)
			# bearing is degrees from north
			bearing = calcBearing((point['lat2'], point['lon2']), (point2['lat2'], point2['lon2']))
		else:
				pass # use last calculated bearing

		last_solar_altitude = solar_altitude # keep track of last solar altitude (work out if sun setting or rising)
		solar_altitude = solar.GetAltitude(point['lat2'], point['lon2'], point_time)
		solar_azimuth = solar.GetAzimuth(point['lat2'], point['lon2'], point_time) # degrees from south
		if (solar_azimuth < 0):
			solar_azimuth_from_north = (180 + abs(solar_azimuth)) % 360
		else:
			solar_azimuth_from_north = (180 - solar_azimuth) % 360

		sun_east_west = ""

		# print("----------------- ")
		# print("minute = " + str(i))
		# print("lat,lon = " + str((point['lat2'], point['lon2'])))
		# print("sun alt = " + str(solar_altitude))
		# print("azimuth (from south) = " + str(solar_azimuth))
		# print("azimuth (from north) = " + str(solar_azimuth_from_north))
		# print("bearing (from north) = " + str(bearing))

		point_values = {}
		point_values['min'] = i
		point_values['lat'] = round(point['lat2'], 4)
		point_values['lng'] = round(point['lon2'], 4)
		point_values['sun_alt'] = round(solar_altitude, 2)
		# point_values['azimuth_from_south'] = round(solar_azimuth, 2)
		point_values['azimuth_from_north'] = round(solar_azimuth_from_north, 2)
		point_values['bearing_from_north'] = round(bearing, 2)


		# sun postion
		if ((solar_azimuth >= 0) and (solar_azimuth <= 180)) or (solar_azimuth < -180):
			sun_east_west = "east"
		if ((solar_azimuth < 0) and (solar_azimuth > -180)) or (solar_azimuth > 180):
			sun_east_west = "west"

		#print("sun pos = " + sun_east_west)
		point_values['sun_east_west'] = sun_east_west

		# sun position from bearing of plane
		if (bearing > solar_azimuth_from_north):
			if (abs(bearing - solar_azimuth_from_north) < 180):
				sun_side = "left"
			else:
				sun_side = "right"				
		else:
			if (abs(solar_azimuth_from_north - bearing) < 180):
				sun_side = "right"
			else:
				sun_side = "left"

		# store which side sun is on
		point_values['sun_side'] = sun_side

		# calculate total minutes left or right
		if (solar_altitude > 0.0):
			if (sun_side == 'left'):
				total_mins_left = total_mins_left + 1
			else:
				total_mins_right = total_mins_right + 1


		# night or day?
		# http://www.timeanddate.com/worldclock/aboutastronomy.html
		sunset_max_alt = 6.0 # 12 is correct but use 6
		if (solar_altitude > sunset_max_alt):
			#print("time of day: day")
			point_values['tod'] = 'day'
			if not has_seen_sunrise:
				mins_to_first_sunrise = mins_to_first_sunrise + 1
			if not has_seen_sunset:
				mins_to_first_sunset = mins_to_first_sunset + 1

		if (solar_altitude > 0.0) and (solar_altitude <= sunset_max_alt):
			if (i == 1):
				# not sure if its rising or setting
				# print("time of day: night")
				point_values['tod'] = 'night'
				#if not has_seen_sunrise:
				#	mins_to_first_sunrise = mins_to_first_sunrise + 1
				#if not has_seen_sunset:
				#	mins_to_first_sunset = mins_to_first_sunset + 1
			else:
				if (last_solar_altitude > solar_altitude):
					#print("time of day: sunset")
					point_values['tod'] = 'sunset'
					has_seen_sunset = True
					if not has_seen_sunrise:
						mins_to_first_sunrise = mins_to_first_sunrise + 1
				else:
					#print("time of day: sunrise")
					point_values['tod'] = 'sunrise'
					has_seen_sunrise = True
					if not has_seen_sunset:
						mins_to_first_sunset = mins_to_first_sunset + 1

		if (solar_altitude < 0.0):
			#print("time of day: night")
			point_values['tod'] = 'night'
			total_mins_night = total_mins_night + 1
			if not has_seen_sunrise:
				mins_to_first_sunrise = mins_to_first_sunrise + 1
			if not has_seen_sunset:
				mins_to_first_sunset = mins_to_first_sunset + 1


		points.append(point_values)
		point_time = point_time + datetime.timedelta(0,60) # add 1 minute


	data = {}
	data['points'] = points

	# print stats
	#print("-------------")
	#print("Flight Stats:")
	#print("total_min_left = " + str(total_mins_left) + " (" + str(round((float(total_mins_left) / float(flight_mins)) * 100)) + "%)")
	#print("total_mins_right = " + str(total_mins_right) + " (" + str(round((float(total_mins_right) / float(flight_mins)) * 100)) + "%)")
	#print("total_mins_night = " + str(total_mins_night) + " (" + str(round((float(total_mins_night) / float(flight_mins)) * 100)) + "%)")

	flight_stats = {}
	flight_stats['total_minutes'] = flight_mins
	flight_stats['total_minutes_left'] = total_mins_left
	flight_stats['total_minutes_right'] = total_mins_right
	flight_stats['percent_left'] = round((float(total_mins_left) / float(flight_mins)) * 100)
	flight_stats['percent_right'] = round((float(total_mins_right) / float(flight_mins)) * 100)
	flight_stats['total_minutes_night'] = total_mins_night
	flight_stats['percent_night'] = round((float(total_mins_night) / float(flight_mins)) * 100)

	flight_stats['mins_to_first_sunrise'] = 0
	if (mins_to_first_sunrise < flight_mins):
		flight_stats['mins_to_first_sunrise'] = mins_to_first_sunrise

	flight_stats['mins_to_first_sunset'] = 0
	if (mins_to_first_sunset < flight_mins):
		flight_stats['mins_to_first_sunset'] = mins_to_first_sunset

	# make jsonp object
	data = {}
	data['flight_points'] = points
	data['flight_stats'] = flight_stats
	print json.dumps(data)


# run main method
main()
