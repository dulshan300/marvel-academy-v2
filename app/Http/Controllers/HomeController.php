<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'featuredCourses' => Course::published()
                ->featured()
                ->with('media')
                ->orderByDesc('rating_average')
                ->orderBy('key_stage')
                ->orderBy('id')
                ->limit(6)
                ->get(),
            'plans' => Plan::active()->get(),
            'testimonials' => Testimonial::published()->limit(10)->get(),
        ]);
    }
}
