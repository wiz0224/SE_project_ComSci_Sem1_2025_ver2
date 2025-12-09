<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendar 2025</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="max-w-4xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border">
    <!-- Top bar -->
    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-white border-b">
      <div class="flex items-center gap-3">
        <!-- Back button -->
        <button onclick="history.back()" class="flex items-center px-3 py-1 border rounded-md hover:bg-gray-100 text-gray-700">
          ← Back
        </button>

        <!-- Window buttons + title -->
        <div class="flex items-center gap-3 ml-4">
          <div class="w-3 h-3 rounded-full bg-red-400"></div>
          <div class="w-3 h-3 rounded-full bg-yellow-300"></div>
          <div class="w-3 h-3 rounded-full bg-green-400"></div>
          <h1 class="ml-3 text-lg font-semibold text-gray-700">Calendar — 2025</h1>
        </div>
      </div>

      <!-- Month navigation -->
      <div class="flex items-center gap-2">
        <button id="prevMonth" class="px-3 py-1 border rounded-md hover:bg-gray-100">◀</button>
        <h2 id="monthTitle" class="text-lg font-medium">Month 2025</h2>
        <button id="nextMonth" class="px-3 py-1 border rounded-md hover:bg-gray-100">▶</button>
      </div>
    </div>

    <!-- Calendar grid -->
    <div class="p-6">
      <div class="grid grid-cols-7 gap-2 text-center text-sm font-semibold text-gray-600 mb-2">
        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
      </div>
      <div id="calendarDays" class="grid grid-cols-7 gap-2 text-center text-gray-700"></div>
    </div>
  </div>

  <script>
    const monthNames = [
      "January","February","March","April","May","June",
      "July","August","September","October","November","December"
    ];
    let month = new Date().getMonth();
    const year = 2025;

    const calendarDays = document.getElementById('calendarDays');
    const monthTitle = document.getElementById('monthTitle');
    const prev = document.getElementById('prevMonth');
    const next = document.getElementById('nextMonth');

    function renderCalendar() {
      calendarDays.innerHTML = '';
      monthTitle.textContent = `${monthNames[month]} ${year}`;
      const firstDay = new Date(year, month, 1).getDay();
      const lastDate = new Date(year, month + 1, 0).getDate();

      // Fill empty days before 1st
      for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.classList.add('border','h-16','rounded-md','bg-gray-50');
        calendarDays.appendChild(empty);
      }

      // Fill days of the month
      for (let day = 1; day <= lastDate; day++) {
        const div = document.createElement('div');
        div.textContent = day;
        div.classList.add('border','h-16','flex','items-start','justify-start','p-2','rounded-md','bg-white','hover:bg-blue-50');
        calendarDays.appendChild(div);
      }
    }

    prev.addEventListener('click', () => {
      month = (month - 1 + 12) % 12;
      renderCalendar();
    });

    next.addEventListener('click', () => {
      month = (month + 1) % 12;
      renderCalendar();
    });

    renderCalendar();
  </script>

  <script>
    // Initial check for dark mode and apply body class
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) document.body.classList.add('dark-mode');
  </script>

</body>
</html>
