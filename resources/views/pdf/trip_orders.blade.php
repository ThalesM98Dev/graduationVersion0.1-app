<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
   
</head>
<body>
    <h1>Trip Details</h1>
    <div>
        <h3>Trip Information</h3>
        <p>Trip Number: {{ $trip->trip_number }}</p>
        <p>Date: {{ $trip->date }}</p>
        <p>Depatrue Hour: {{ $trip->depature_hour }}</p>
        <p>Arrival Hour: {{ $trip->arrival_hour }}</p>
        <p>Destination: {{ $trip->destination->name }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Mobile Number</th>
                <th>Nationality</th>
                <th>Age</th>
                <th>Seat Number</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trip->orders as $order)
                <tr>
                    <td>{{ $order->name }}</td>
                    <td>{{ $order->address }}</td>
                    <td>{{ $order->mobile_number }}</td>
                    <td>{{ $order->nationality }}</td>
                    <td>{{ $order->age }}</td>
                    <td>{{ $order->seat_number }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>