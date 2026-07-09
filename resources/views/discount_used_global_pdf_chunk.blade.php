<table>
    <thead>
    <tr>
        <th>شناسه</th>
        <th>نام</th>
        <th>کد</th>
        <th>مقدار</th>
        <th>نوع</th>
        <th>استفاده</th>
        <th>وضعیت</th>
        <th>تاریخ ایجاد</th>
    </tr>
    </thead>
    <tbody>
    @foreach($discounts as $discount)
        <tr>
            <td>{{ $discount['id'] }}</td>
            <td>{{ $discount['name'] }}</td>
            <td>{{ $discount['code'] }}</td>
            <td>{{ $discount['discount_value'] }}</td>
            <td>{{ $discount['discount_type'] }}</td>
            <td>{{ $discount['usage'] }}</td>
            <td>{{ $discount['is_active'] }}</td>
            <td>{{ $discount['created_at'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>