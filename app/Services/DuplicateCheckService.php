<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Str;

class DuplicateCheckService
{
    /**
     * Check if registration is a potential duplicate
     *
     * @param array $data Registration data
     * @param int $eventId Event ID
     * @return bool|Registration Returns false if no duplicate, or the duplicate registration
     */
    public function checkDuplicate(array $data, $eventId)
    {
        // Check for exact match on email for the same event
        $emailMatch = Registration::where('event_id', $eventId)
                     ->where('email', $data['email'])
                     ->first();
        
        if ($emailMatch) {
            return $emailMatch;
        }
        
        // Check for phone match if phone is provided
        if (!empty($data['phone'])) {
            $phoneMatch = Registration::where('event_id', $eventId)
                         ->where('phone', $data['phone'])
                         ->first();
            
            if ($phoneMatch) {
                return $phoneMatch;
            }
        }
        
        // Check for name + company match
        if (!empty($data['company'])) {
            $nameCompanyMatch = Registration::where('event_id', $eventId)
                               ->where('name', 'like', $data['name'])
                               ->where('company', 'like', $data['company'])
                               ->first();
            
            if ($nameCompanyMatch) {
                return $nameCompanyMatch;
            }
        }
        
        // No duplicate found
        return false;
    }
    
    /**
     * Calculate similarity between registrations
     *
     * @param Registration $registration1
     * @param Registration $registration2
     * @return float Similarity score between 0-1
     */
    public function calculateSimilarity(Registration $registration1, Registration $registration2)
    {
        $score = 0;
        $totalFactors = 0;
        
        // Compare email (weighted heavily)
        if ($registration1->email == $registration2->email) {
            $score += 0.5;
        } elseif (strtolower($registration1->email) == strtolower($registration2->email)) {
            $score += 0.4;
        } elseif (levenshtein(strtolower($registration1->email), strtolower($registration2->email)) <= 3) {
            $score += 0.3;
        }
        $totalFactors += 0.5;
        
        // Compare name
        similar_text(
            strtolower($registration1->name),
            strtolower($registration2->name),
            $nameSimilarity
        );
        $score += ($nameSimilarity / 100) * 0.25;
        $totalFactors += 0.25;
        
        // Compare phone if available
        if ($registration1->phone && $registration2->phone) {
            $phone1 = preg_replace('/\D/', '', $registration1->phone);
            $phone2 = preg_replace('/\D/', '', $registration2->phone);
            
            if ($phone1 == $phone2) {
                $score += 0.15;
            } elseif (levenshtein($phone1, $phone2) <= 2) {
                $score += 0.1;
            }
            $totalFactors += 0.15;
        }
        
        // Compare company if available
        if ($registration1->company && $registration2->company) {
            similar_text(
                strtolower($registration1->company),
                strtolower($registration2->company),
                $companySimilarity
            );
            $score += ($companySimilarity / 100) * 0.1;
            $totalFactors += 0.1;
        }
        
        // Normalize score based on available factors
        return $totalFactors > 0 ? $score / $totalFactors : 0;
    }
}