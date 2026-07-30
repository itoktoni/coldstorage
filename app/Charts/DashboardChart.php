<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class DashboardChart
{
    public function build(): LarapexChart
    {
        return (new LarapexChart)->barChart()
            ->setTitle('Sales Overview')
            ->setSubtitle('Daily revenue - May 2026')
            ->addData([1.2, 1.8, 2.1, 1.5, 2.4, 2.8, 1.9, 2.2, 3.0, 2.5, 1.7, 2.9, 3.2, 2.1], 'Revenue (Jt)')
            ->setXAxis(range(1, 14))
            ->setColors(['#27272a']);
    }
}
