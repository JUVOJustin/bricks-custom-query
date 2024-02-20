<?php

namespace juvo\Bricks_Custom_Queries;

enum Query_Type
{
    case Post;
    case User;
    case Term;
    case Other;
}