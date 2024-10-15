<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        @media print {
            @page {
                size: 76mm 60mm; /* Set custom size: 76mm width, 60mm height */
                margin: 5mm; /* Adjust margins as necessary */
            }

            body {
                margin: 0; /* Remove default margin */
                font-size: 10px; /* Set a smaller font size for printing */
            }

            h1 {
                font-size: 14px; /* Reduce font size of the heading */
                margin-bottom: 5px; /* Reduce space below the heading */
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 5px; /* Reduce margin above the table */
            }

            th, td {
                border: 1px solid #ddd;
                padding: 2px; /* Reduce padding for compactness */
                text-align: left;
            }

            th {
                background-color: #4CAF50;
                color: white;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            .total {
                font-weight: bold;
                font-size: 12px; /* Set a slightly larger font for total */
                text-align: right;
                margin-top: 5px; /* Reduce space above the total */
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px; /* Default padding for normal view */
            background-color: #f4f4f4;
        }
    </style>
    <script>
        function printPDF() {
            setTimeout(function() {
                window.print(); // Invoke print dialog after a short delay
            }, 100); // Adjust delay as necessary
        }
    </script>
</head>
<body onload="printPDF()">
    <h1>Invoice #{{ $order->id }}</h1>
    <p>Date: {{ $order->created_at }}</p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->product->price, 2) }}</td>
                    <td>{{ number_format($item->quantity * $item->product->price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Total: {{ number_format($order->total_price, 2) }}</p>
    <!-- Add other order details as necessary -->
</body>
</html>
